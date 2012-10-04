<?php

class BTwitterAdapter extends BClass
{
    protected static $_connection;

    public static function bootstrap()
    {
        require_once (__DIR__.'/lib/twitteroauth/twitteroauth.php');

        BFrontController::i()
            ->route('GET|POST /a/twitter/.action', 'BTwitterAdapter_Controller')
        ;

        BLayout::i()
            ->addAllViews('views')
            ->addLayout(array(
                'base' => array(
                    array('hook', 'login-after', 'views'=>array('twitter-login')),
                    array('hook', 'signup-after', 'views'=>array('twitter-login')),
                    array('hook', 'newpost-flags', 'views'=>array('twitter-newpost')),
                ),
            ))
        ;

        BPubSub::i()
            ->on('BModelUser::login.after', 'BTwitterAdapter::onUserLogin')
            ->on('BModelUser::logout.after', 'BTwitterAdapter::onUserLogout')
            ->on('DotMesh_Model_Post::submitNewPost.before', 'BTwitterAdapter::onNewPostBefore')
            ->on('DotMesh_Model_Post::submitNewPost.after', 'BTwitterAdapter::onNewPostAfter')
        ;
    }

    public static function connection()
    {
        if (!static::$_connection) {
            $sess = BSession::i()->data('twitter');
            $access = $sess['access_token'];
            static::$_connection = new TwitterOAuth($conf['consumer_key'], $conf['consumer_secret'],
                $access['oauth_token'], $access['oauth_token_secret']);
        }
        return static::$_connection;
    }
    
    public static function onNewPostBefore($args)
    {
        $args['data']['is_tweeted'] = $args['request']['is_tweeted'] ? 1 : 0;
    }

    public static function onNewPostAfter($args)
    {
        $post = $args['post'];
        if (!$post->is_tweeted) {
            return;
        }
        $uri = $post->uri();
        if (strlen($post->preview)>135 || $post->preview!==$post->contents) {
            $status = substr($post->contents, 0, 130-strlen($uri)).'... '.$uri;
        } else {
            $status = $post->contents;
        }
        $response = static::connection()->post('statuses/update', array('status'=>$status));

        #echo $status."<pre>"; print_r($response); exit;
        //TODO: process response?
    }

    public static function onUserLogin($args)
    {
        if ($args['user']->twitter_data) {
            $sess =& BSession::i()->dataToUpdate();
            $data = BUtil::fromJson($args['user']->twitter_data);
            $sess['twitter']['access_token'] = $data['access_token'];
        }
    }

    public static function onUserLogout($args)
    {
        $sess =& BSession::i()->dataToUpdate();
        unset($sess['twitter']);
    }

    public static function migrate()
    {
        BMigrate::install('0.1.0', function() {
            BDb::ddlTableColumns(DotMesh_Model_User::table(), array(
                'twitter_screenname' => 'VARCHAR(50)',
                'twitter_data' => 'TEXT',
            ), array(
                'IDX_node_twitter_screenname' => '(node_id, twitter_screenname)',
            ));
            BDb::ddlTableColumns(DotMesh_Model_Post::table(), array(
                'is_tweeted' => 'TINYINT NOT NULL DEFAULT 0',
            ));
        });
    }
}

class BTwitterAdapter_Controller extends BActionController
{
    public function action_redirect()
    {
        $to = BRequest::i()->get('to');
        if ($to==='post' && !DotMesh_Model_User::i()->isLoggedIn()) {
            echo BLocale::i()->_("Not Logged In");
            exit;
        }
        $sess =& BSession::i()->dataToUpdate();
        $conf = BConfig::i()->get('modules/BTwitterAdapter');

        /* Build TwitterOAuth object with client credentials. */
        $connection = new TwitterOAuth($conf['consumer_key'], $conf['consumer_secret']);

        /* Get temporary credentials. */
        $callbackUrl = BUtil::setUrlQuery(BApp::href('a/twitter/callback'), array('to'=>$to));
        $request_token = $connection->getRequestToken($callbackUrl);

        /* Save temporary credentials to session. */
        $sess['twitter']['oauth_token'] = $token = $request_token['oauth_token'];
        $sess['twitter']['oauth_token_secret'] = $request_token['oauth_token_secret'];

        /* If last connection failed don't display authorization link. */
        switch ($connection->http_code) {
            case 200:
                /* Build authorize URL and redirect user to Twitter. */
                $url = $connection->getAuthorizeURL($token);
                header('Location: ' . $url);
                break;
            default:
                /* Show notification if something went wrong. */
                echo 'Could not connect to Twitter. Refresh the page or try again later.';
        }

    }

    public function action_callback()
    {
        $to = BRequest::i()->get('to');

        $sess =& BSession::i()->dataToUpdate();
        $conf = BConfig::i()->get('modules/BTwitterAdapter');

        /* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
        $connection = new TwitterOAuth($conf['consumer_key'], $conf['consumer_secret'],
            $sess['twitter']['oauth_token'], $sess['twitter']['oauth_token_secret']);

        /* Request access tokens from twitter */
        $accessToken = $connection->getAccessToken($_REQUEST['oauth_verifier']);

        /* Remove no longer needed request tokens */
        unset($sess['twitter']['oauth_token']);
        unset($sess['twitter']['oauth_token_secret']);
//var_dump($connection); exit;

        /* If HTTP response is 200 continue otherwise send to connect page to retry */
        if (200 == $connection->http_code) {
            $screenName = $accessToken['screen_name'];
            $twitterData = array(
                'access_token' => $accessToken,
                'account' => (array)$connection->get('account/verify_credentials'),
            );

            switch ($to) {
            case '': case 'login':
//echo "<pre>"; var_dump($connection->get('account/verify_credentials')); exit;
                $node = DotMesh_Model_Node::i()->localNode();
                $user = $node->user($screenName, 'twitter_screenname');
                if (!$user) {
                    $user = $node->user($screenName);
                }
                if ($user) {
                    $user->set(array(
                        'twitter_screenname' => $screenName,
                        'twitter_data' => BUtil::toJson($twitterData),
                    ))->save();
                } else {
                    list($firstname, $lastname) = explode(' ', $twitterData['account']['name'], 2)+array('');
                    $data = array(
                        'node_id' => $node->id,
                        'username' => $screenName,
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'secret_key' => BUtil::randomString(64),
                        'thumb_provider' => 'link',
                        'thumb_uri' => $twitterData['account']['profile_image_url_https'],
                        'twitter_screenname' => $screenName,
                        'twitter_data' => BUtil::toJson($twitterData),
                    );
                    $user = DotMesh_Model_User::i()->create($data)->save();
                }
                $user->login();

            case 'post':
                $user = DotMesh_Model_User::i()->sessionUser();
                //TODO: improve logged in check
                if (!DotMesh_Model_User::i()->isLoggedIn()) {
                    echo BLocale::i()->_("Not Logged In");
                    exit;
                }
                $sess['twitter']['access_token'] = $accessToken;
                $user->set('twitter_data', BUtil::toJson($twitterData))->save();
                break;
            }

            /* The user has been verified and the access tokens can be saved for future use */
            $sess['twitter']['status'] = 'verified';
            echo '<script>';
            switch ($to) {
            case '': case 'login':
                echo 'window.opener.location.reload();';
                break;
            case 'post':
                echo 'window.opener.toggleTwitterPost("'.addslashes($screenName).'");';
                break;
            }
            echo 'window.close();</script>';
        } else {
            /* Save HTTP status for error dialog on connnect page.*/
            $sess['twitter']['status_code'] = $connection->http_code;
            $errMsg = BLocale::i()->_('There was a problem signing in to Twitter');
            echo '<script>alert("'.$errMsg.'"); window.close();</script>';
        }

    }
}