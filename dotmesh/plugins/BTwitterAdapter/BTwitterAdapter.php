<?php

class BTwitterAdapter extends BClass
{
    protected static $_connection;
    
    public static function bootstrap()
    {
        require_once (__DIR__.'/lib/twitteroauth/twitteroauth.php');
        
        BFrontController::i()
            ->route('GET|POST /twitter/.action', 'BTwitterAdapter_Controller')
        ;
        
        BLayout::i()
            ->addAllViews('views')
            ->addLayout(array(
                'base' => array(
                    array('hook', 'newpost-flags', 'views'=>array('twitter-newpost')),
                ),
            ));
            
        BPubSub::i()
            ->on('BModelUser::login.after', 'BTwitterAdapter::onUserLogin')
            ->on('BModelUser::logout.after', 'BTwitterAdapter::onUserLogout')
            ->on('DotMesh_Model_Post::submitNewPost.after', 'BTwitterAdapter::onNewPost')
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
    
    public static function onNewPost($args)
    {
        $post = $args['post'];
        $uri = $post->uri();
        if (strlen($post->preview)>135 || $post->preview!==$post->contents) {
            $status = substr($post->contents, 0, 130-strlen($uri)).'... '.$uri;
        } else {
            $status = $post->contents;
        }
        $response = static::connection()->post('statuses/update', array('status'=>$status));

#echo $status."<pre>"; print_r($response); exit;
        //TODO: process response?
        $post->set('is_tweeted', 1)->save();
    }
    
    public static function onUserLogin($args)
    {
        if ($args['user']->twitter_data) {
            $sess =& BSession::i()->dataToUpdate();
            $sess['twitter']['access_token'] = BUtil::fromJson($args['user']->twitter_data);
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
                'twitter_data' => 'text',
            ));
            BDb::ddlTableColumns(DotMesh_Model_Post::table(), array(
                'is_tweeted' => 'tinyint not null default 0',
            ));
        });
    }
}

class BTwitterAdapter_Controller extends BActionController
{
    public function action_redirect()
    {
        $sess =& BSession::i()->dataToUpdate();
        $conf = BConfig::i()->get('modules/BTwitterAdapter');
        
        /* Build TwitterOAuth object with client credentials. */
        $connection = new TwitterOAuth($conf['consumer_key'], $conf['consumer_secret']);
         
        /* Get temporary credentials. */
        $request_token = $connection->getRequestToken(BApp::href('twitter/callback'));

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
        $sess =& BSession::i()->dataToUpdate();
        $conf = BConfig::i()->get('modules/BTwitterAdapter');
            
        /* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
        $connection = new TwitterOAuth($conf['consumer_key'], $conf['consumer_secret'], 
            $sess['twitter']['oauth_token'], $sess['twitter']['oauth_token_secret']);

        /* Request access tokens from twitter */
        $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

        /* Save the access tokens. Normally these would be saved in a database for future use. */
        $sess['twitter']['access_token'] = $access_token;
        DotMesh_Model_User::i()->sessionUser()->set('twitter_data', BUtil::toJson($access_token))->save();

        /* Remove no longer needed request tokens */
        unset($sess['twitter']['oauth_token']);
        unset($sess['twitter']['oauth_token_secret']);

        /* If HTTP response is 200 continue otherwise send to connect page to retry */
        if (200 == $connection->http_code) {
          /* The user has been verified and the access tokens can be saved for future use */
          $sess['twitter']['status'] = 'verified';
?>
<script>
window.opener.toggleTwitterPost('<?=addslashes($access_token['screen_name'])?>');
window.close();
</script>        
<?php
        } else {
          /* Save HTTP status for error dialog on connnect page.*/
          $sess['twitter']['status_code'] = $connection->http_code;
?>
<script>
alert('There was a problem signing in to Twitter');
window.close();
</script>        
<?php
        }

    }
}