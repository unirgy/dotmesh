<?php

BModuleRegistry::i()->addModule('DotMesh', array(
    'version' => '0.1.0',
    'bootstrap' => array('callback'=>'DotMesh::bootstrap'),
));

BConfig::i()->add(array(
    'request' => array(
        'module_run_level' => array('DotMesh' => 'REQUIRED'),
    ),
));

class DotMesh extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route('GET /', 'DotMesh_Controller.index')
            ->route('GET /:term', 'DotMesh_Controller.catch_all')


            ->route('GET|POST /a/.action', 'DotMesh_Controller_Account')
            ->route('GET|POST /n/.action', 'DotMesh_Controller_Node')
            
            ->route('GET|POST|PUT|HEAD|OPTIONS /u/:username', 'DotMesh_Controller_User.index')
            ->route('GET|POST|PUT|HEAD|OPTIONS /p/:postname', 'DotMesh_Controller_Post.index')
            ->route('GET|POST|PUT|HEAD|OPTIONS /t/:tagname', 'DotMesh_Controller_Tag.index')

            ->route('^(GET|POST|PUT|HEAD|OPTIONS) /u/([a-zA-Z0-9_]+)\.json$', 'DotMesh_Controller_User.json')
            ->route('^(GET|POST|PUT|HEAD|OPTIONS) /p/([a-zA-Z0-9_]+)\.json$', 'DotMesh_Controller_Post.json')
            ->route('^(GET|POST|PUT|HEAD|OPTIONS) /t/([a-zA-Z0-9_]+)\.json$', 'DotMesh_Controller_Tag.json')

            ->route('^GET /u/([a-zA-Z0-9_]+)\.rss$', 'DotMesh_Controller_User.rss')
            ->route('^GET /p/([a-zA-Z0-9_]+)\.rss$', 'DotMesh_Controller_Post.rss')
            ->route('^GET /t/([a-zA-Z0-9_]+)\.rss$', 'DotMesh_Controller_Tag.rss')

            ->route('^GET /u/([a-zA-Z0-9_]+)\.(png|jpg|gif)$', 'DotMesh_Controller_User.thumb')
        ;

        BLayout::i()
            ->addView('head', array('view_class'=>'BViewHead'))
            ->addAllViews('views')

            ->addLayout(array(
                'base' => array(
                    array('hook', 'head', 'views'=>array('head')),
                    array('hook', 'header', 'views'=>array('header')),
                    array('hook', 'footer', 'views'=>array('footer')),
                    array('view', 'head', 'do'=>array(
                        array('js', '{DotMesh}/css/dotmesh.css'),
                        array('js', '//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js'),
                        //array('js', '{DotMesh}/js/jquery.min.js'),
                        array('js', '{DotMesh}/js/dotmesh.js'),
                    )),
                ),
                '/' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('home')),
                ),
                '/setup' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('setup')),
                ),
                '/signup' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('signup')),
                ),
                '/search' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('search')),
                ),
                '/post' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('post')),
                ),
                '/user' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('user')),
                ),
                '/tag' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('tag')),
                ),
            ));
        ;
    }
}

/************************************************************************/

class DotMesh_Controler_Abstract extends BActionController
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) {
            return false;
        }
        $r = BRequest::i();
        if (!DotMesh_Model_Node::i()->localNode() && $r->rawPath()!=='/a/setup') {
            BResponse::i()->redirect(BApp::href('a/setup'));
        }
        if (($guest = $r->get('guest_uri'))) {
            DotMesh_Model_User::i()->acceptGuest($guest, $r->get('guest_signature'));
        }
        return true;
    }

    public function afterDispatch()
    {
        parent::afterDispatch();
        BResponse::i()->output();
    }
}

class DotMesh_Controller extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        BLayout::i()->applyLayout('/');
        if (DotMesh_Model_User::isLoggedIn()) {
            $timeline = DotMesh_Model_Post::i()->myTimelineOrm()->find_many();
        } else {
            $timeline = array();
        }
        BLayout::i()->view('timeline')->set('timeline', $timeline);
    }

    public function action_catch_all()
    {

    }
}

class DotMesh_Controller_Account extends DotMesh_Controler_Abstract
{
    public function action_setup()
    {
        if (DotMesh_Model_Node::i()->localNode()) {
            BResponse::i()->redirect(BApp::href());
        }
        $r = BRequest::i();
        BLayout::i()->view('setup')->set(array(
            'node_uri' => trim($r->httpHost().'/'.$r->webRoot(), '/'),
            'is_https' => $r->https(),
            'is_modrewrite' => $r->modRewriteEnabled(),
        ));
        BLayout::i()->applyLayout('/setup');
    }

    public function action_setup__POST()
    {
        $redirectUrl = BApp::href();
        try {
            if (DotMesh_Model_Node::i()->localNode()) {
                BResponse::i()->redirect(BApp::href());
            }
            $form = BRequest::i()->post('setup');
            $node = DotMesh_Model_Node::i()->setup($form);
        } catch (BException $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
    }

    public function action_login()
    {
        BLayout::i()->applyLayout('/login');
    }

    public function action_login__POST()
    {
        $r = BRequest::i();
        $redirectUrl = $r->referrer() ? $r->referrer() : BApp::href();
        try {
            if ($r->xhr()) {
                $form = $r->json();
            } else {
                $form = $r->post('login');
            }
            if (empty($form['username']) || empty($form['password'])) {
                throw new BException('Missing username or password');
            }
            $user = DotMesh_Model_User::i()->authenticate($form['username'], $form['password']);
            if (!$user) {
                throw new BException('Invalid username or password');
            }
            $user->login();
            $result = array('status'=>'success', 'message'=>'Login successful');
        } catch (BException $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        if ($r->xhr()) {
            BResponse::i()->json($result);
        } else {
            BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
        }
    }

    public function action_signup()
    {
        BLayout::i()->applyLayout('/signup');
        BResponse::i()->output();
    }

    public function action_signup__POST()
    {
        $r = BRequest::i();
        $redirectUrl = BApp::href();
        try {
            if ($r->xhr()) {
                $form = $r->json();
            } else {
                $form = $r->post('signup');
            }
            $user = Denteva_Model_User::i()->signup($form);
            $result = array('status'=>'success', 'message'=>'Sign up successful');
        } catch (BException $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        if ($r->xhr()) {
            BResponse::i()->json($result);
        } else {
            BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
        }
    }

    public function action_password_recover()
    {
        BLayout::i()->applyLayout('/password_recover');
    }

    public function action_password_recover__POST()
    {

        BResponse::i()->redirect(BApp::href());
    }

    public function action_password_reset()
    {
        BLayout::i()->applyLayout('/password_reset');
    }

    public function action_password_reset__POST()
    {

        BResponse::i()->redirect(BApp::href());
    }

    public function action_logout()
    {
        if (($user = DotMesh_Model_User::i()->sessionUser())) {
            $user->logout();
        }
        BResponse::i()->redirect(BApp::href());
    }
}

class DotMesh_Controller_Node extends DotMesh_Controler_Abstract
{
    public function action_index()
    {

    }
    
    public function action_index__POST()
    {
        $r = BRequest::i();
        $request = $r->json();
        
    }
}

class DotMesh_Controller_User extends DotMesh_Controler_Abstract
{
    public function action_json()
    {

    }

    public function action_rss()
    {

    }
}

class DotMesh_Controller_Post extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        $hlp = DotMesh_Model_Post::i();
        $postname = $r->param('postname');
        if ($postname) {
            $post = $hlp->load($postname, 'postname');
            if (!$post) {
                throw new BException('Invalid post identifier');
            }
        }
    }
    
    public function action_index__POST()
    {
        try {
            $redirectUrl = BApp::href();
            if (!DotMesh_Model_User::isLoggedIn()) {
                throw new BException('Not logged in');
            }
            $r = BRequest::i();
            $hlp = DotMesh_Model_Post::i();
            $postname = $r->param('postname');
            if ($postname) {
                $post = $hlp->load($postname, 'postname');
                if (!$post) {
                    throw new BException('Invalid post identifier');
                }
            }
            $do = $r->post('do');
            switch ($do) {
            case 'new':
                if ($post) {
                    throw new BException('Invalid post action');
                }
                $post = $hlp->submitNewPost($r->post());
                $result = $post->result;
                break;
            case 'star': case 'un-star':
                $post->submitFeedback('star', $do=='star' ? 1 : 0);
                break;
            case 'report': case 'un-report':
                $post->submitFeedback('report', $do=='report' ? 1 : 0);
                break;
            case 'score-up': case 'un-score-up':
                $post->submitFeedback('score', $do=='score-up' ? 1 : 0);
                break;
            case 'score-down': case 'un-score-down':
                $post->submitFeedback('score', $do=='score-down' ? -1 : 0);
                break;
            case 'delete':
                if (DotMesh_Model_User::sessionUserId()!==$post->user_id) {
                    throw new BException('Post does not belong to logged in user');
                }
                $post->delete();
                break;
            }
            $result['status'] = 'success';
            $result['message'] = 'Your post has been submited';
        } catch (BException $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        if ($r->xhr()) {
            BResponse::i()->json($result);
        } else {
            BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
        }
    }

    public function action_json()
    {

    }

    public function action_rss()
    {

    }
}

class DotMesh_Controller_Tag extends DotMesh_Controler_Abstract
{

    public function action_json()
    {

    }

    public function action_rss()
    {

    }
}

/************************************************************************/

class DotMesh_Model_Node extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_node';

    protected static $_localNode;
    
    /**
    * Shortcut to help with IDE autocompletion
    * 
    * @return DotMesh_Model_Node
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    public static function localNode()
    {
        if (is_null(static::$_localNode)) {
            static::$_localNode = static::load(1);
        }
        return static::$_localNode;
    }

    public static function setup($form)
    {
        try {
            if (empty($form['node_uri'])
                || empty($form['username']) || empty($form['email']) || empty($form['name'])
                || empty($form['password']) || empty($form['password_confirm'])
                || $form['password'] !== $form['password_confirm']
                || !isset($form['is_https'])
            ) {
                throw new BException('Missing or invalid input');
            }
            $node = DotMesh_Model_Node::i()->create(array(
                'id' => 1,
                'uri' => $form['node_uri'],
                'is_local' => 1,
                'is_https' => $form['is_https'],
                'is_modrewrite' => $form['is_modrewrite'],
                'private_key' => BUtil::randomString(60),
            ))->save();
            $user = DotMesh_Model_User::i()->create(array(
                'node_id' => $node->id,
                'username' => $form['username'],
                'name' => $form['name'],
                'email' => $form['email'],
                'private_key' => BUtil::randomString(60),
            ))->setPassword($form['password'])->save()->login();

            //BLayout::i()->view('email/user-new-user')->set('user', $user)->email();
            //BLayout::i()->view('email/admin-new-user')->set('user', $user)->email();

            $redirectUrl = BApp::href();
            $result = array('status'=>'success', 'message'=>'Thank you for setting up DotMesh node!');
        } catch (BException $e) {
            $redirectUrl = BApp::href('a/setup');
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        } catch (Exception $e) {
            $redirectUrl = BApp::href('a/setup');
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        if (BRequest::i()->xhr()) {
            BResponse::i()->json($result);
        } else {
            BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
        }
    }
    
    public static function find($uri, $create=false, $fetch=false) 
    {
        if (strpos($uri, '?')!==false || strpos($uri, '#')!==false) {
            throw new BException('Invalid node URI');
        }
        $node = static::load($uri, 'uri');
        if (!$node && $create) {
            $node = static::create(array('uri'=>$uri));
            if ($fetch) {
                $node->fetchInfo();
            }
            $node->save();
        }
        return $node;
    }
    
    public function fetchInfo() 
    {
        $result = BUtil::remoteHttp('POST', $this->uri.'/n/');
        $info = BUtil::fromJson($result);
        $this->is_https = $info['is_https'];
        $this->is_modrewrite = $info['is_modrewrite'];
        return $this;
    }
    
    public function uri($type=null, $full=false) 
    {
        $uri = '';
        if ($full) {
            $uri .= $this->is_https ? 'https://' : 'http://';
        }
        $uri .= trim($this->uri,'/');
        if ($type) {
            $uri .= '/'.$type.'/';
        }
        return $uri;
    }
}

class DotMesh_Model_Post extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_post';
    
    /**
    * Shortcut to help with IDE autocompletion
    * 
    * @return DotMesh_Model_Post
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }
    
    public static function timelineOrm()
    {
        $orm = static::orm('p')
            ->select('p.*')
            ->join('DotMesh_Model_Node', array('n.id','=','p.node_id'), 'n')
            ->join('DotMesh_Model_User', array('u.id','=','p.user_Id'), 'u')
            ->left_outer_join('DotMesh_Model_User', array('eu.id','=','p.echo_user_id'), 'eu')
            ->order_by_desc('p.create_dt')
        ;
        if (($uId = (int)DotMesh_Model_User::sessionUserId())) {
            $orm->left_outer_join('DotMesh_Model_PostFeedback', "pf.post_id=p.id and pf.user_id={$uId}", 'pf')
                ->select(array('pf.star', 'user_score'=>'pf.score', 'pf.report'));
        }
        return $orm;
    }
    
    public static function myTimelineOrm()
    {
        $nodeBlock = DotMesh_Model_NodeBlock::table();
        $userBlock = DotMesh_Model_UserBlock::table();
        $postUser = DotMesh_Model_PostUser::table();
        $postTag = DotMesh_Model_PostTag::table();
        $userSub = DotMesh_Model_UserSub::table();
        $tagSub = DotMesh_Model_TagSub::table();
        
        $uId = (int)DotMesh_Model_User::sessionUserId();
        $orm = static::timelineOrm();
        
        $orm->where(array('OR' => array(
            "p.user_id={$uId} or p.echo_user_id={$uId}", // post is made or echoed by logged in user
            'AND' => array(
                'n.is_blocked=0', // post node is not globally blocked
                'u.is_blocked=0', // post user is not globally blocked
                'p.echo_user_id is null or eu.is_blocked=0', // post is not echoed by globally blocked user
                "p.node_id not in (select block_node_id from {$nodeBlock} where user_id={$uId})", // post node is not blocked by user
                "p.user_id not in (select block_user_id from {$userBlock} where user_id={$uId})", // post user is not blocked by user
                'OR' => array(
                    "p.id in (select post_id from {$postUser} where user_id={$uId})", // logged in user mentioned in the post
                    'AND' => array(
                        'p.is_private=0', // post is public
                        'OR' => array( // post is by user or tag logged in user is subscribed to
                            "p.user_id in (select pub_user_id from {$userSub} where sub_user_id={$uId})", 
                            "p.id in (select post_id from {$postTag} pt inner join {$tagSub} ts on ts.pub_tag_id=pt.tag_id where ts.sub_user_id={$uId})",
                            'AND' => array( // or echoed by user i subscribed to and it's not blocked by me
                                "p.echo_user_id in (select pub_user_id from {$userSub} where sub_user_id={$uId})",
                                "p.echo_user_id not in (select block_user_id from {$userBlock} where user_id={$uId})",
                            ),
                        ),
                    ),
                ),
            ),
        )));

        return $orm;
    }
    
    public static function nextSeqName()
    {
        $max = static::orm()->select('(max(postname))', 'name')->find_one();
        return $max ? BUtil::nextStringValue($max->name) : '1';
    }
    
    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->create_dt) $this->create_dt = BDb::now();
        $this->update_dt = BDb::now();
        return true;   
    }
    
    public static function submitNewPost($r)
    {
        $contentLines = explode("\n", trim($r['contents']));
        $preview = sizeof($contentLines)>1 ? $contentLines[0] : BUtil::previewText($r['contents'], 140);
        
        $post = static::create(array(
            'node_id' => DotMesh_Model_Node::localNode()->id,
            'postname' => static::nextSeqName(),
            'parent_post_id' => !empty($r['parent_post_id']) ? $r['parent_post_id'] : null,
            'user_id' => DotMesh_Model_User::sessionUserId(),
            'preview' => $preview,
            'contents' => $r['contents'],
            'is_private' => !empty($r['is_private']),
        ))->save();
        
        return $post;
    }
    
    public static function receiveRemotePost($r)
    {
        $post = static::create(array(
            //'node_id' => $r->node
        ))->save();
        return $post;
    }
    
    public function uri($full=null)
    {
        $node = DotMesh_Model_Node::i()->load($this->node_id);
        return $node->uri('p', $full).$this->postname;
    }
    
    public static function formatContentsHtml($contents)
    {
        $re = '|([@#])([a-zA-Z0-9_]+)|';
        $contents = preg_replace_callback($re, function($m) {
            switch ($m[1]) {
            case '@':
                return '<a href="https://twitter.com/'.urlencode($m[2]).'">'.htmlspecialchars($m[0]).'</a>';
            case '#':
                return '<a href="https://twitter.com/#!/search/?q='.urlencode($m[0]).'">'.htmlspecialchars($m[0]).'</a>';
            }
        }, $contents);
        
        $re = '|([+@^#])([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/([a-zA-Z0-9_-]+)|';
        $contents = preg_replace_callback($re, function($m) {
            $node = DotMesh_Model_Node::i()->find($m[2].$m[3], true);
            switch ($m[1]) {
            case '+': case '@':
                $user = DotMesh_Model_User::i()->find($m[2].$m[3].'/'.$m[4], true);
                return '<a href="'.$user->uri(true).'">'.$m[0].'</a>';
            case '^': case '#': 
                $tag = DotMesh_Model_Tag::i()->find($m[2].$mp[3].'/'.$m[4], true);
                return '<a href="'.$tag->uri(true).'">'.$m[0].'</a>';
            }
        }, $contents);
        
        return $contents;
    }
    
    public function previewHtml()
    {
        return static::formatContentsHtml($this->preview);
    }
    
    public function contentsHtml()
    {
        return static::formatContentsHtml($this->contents);
    }
    
    public function submitFeedback($type, $value, $userId=null)
    {
        if (!in_array($type, array('star', 'score', 'report'))) {
            throw new BException('Invalid feedback type: '.$type);
        }
        if (is_null($userId)) {
            $userId = DotMesh_Model_User::sessionUserId();
        }
        if (!$userId) {
            throw new BException('Invalid user');
        }
        $data = array('post_id'=>$this->id, 'user_id'=>$userId);
        $fb = DotMesh_Model_PostFeedback::i()->load($data);
        if (!$fb) {
            $fb = DotMesh_Model_PostFeedback::i()->create($data)->set($type, $value)->save();
        } else {
            DotMesh_Model_PostFeedback::i()->update_many(array($type=>$value), $data);
        }
        return $this;
    }
}

class DotMesh_Model_User extends BModelUser
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_user';
    
    /**
    * Shortcut to help with IDE autocompletion
    * 
    * @return DotMesh_Model_User
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }
    
    public static function signup($r)
    {
        $user = parent::signup($r);
        $user->set('private_key', BUtil::randomString(32))->save();
        return $user;
    }
    
    public function sendEmailConfirmation()
    {
        BLayout::i()->view('email/user-confirm')->email();
        return $this;
    }

    public function confirmEmail()
    {
        $this->set('is_confirmed', 1)->save();
        return $this;
    }
    
    public function userTimelineOrm()
    {
        $postUser = DotMesh_Model_PostUser::table();
        
        $uId = (int)DotMesh_Model_User::sessionUserId();
        $orm = DotMesh_Model_Post::i()->timelineOrm();
        
        $orm->where(array('AND'=>array(
            "p.user_id={$pubUserId} or p.echo_user_id={$pubUserId}",
            "p.is_private=0".($uId ? " or p.id in (select post_id from {$postUser} where user_id={$uId})" : ''),
        )));
        
        return $orm;
    }
        
    public static function parseUri($uri) 
    {
        $uri = str_replace('/u/', '/', $uri);
        $re = '|([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/([a-zA-Z0-9_-]+)|';
        if (!preg_match($re, $uri, $m)) {
            return false;
        }
        return array($m[1].$m[2], $m[3]);
    }
    
    public static function find($uri, $create=false) 
    {
        list($nodeUri, $username) = static::parseUri($uri);
        $node = DotMesh_Model_Node::i()->find($nodeUri, $create);
        //$node->is_blocked?
        $data = array('node_id'=>$node->id, 'username'=>$username);
        $user = static::load($data);
        if (!$user && $create) {
            $user = static::create($data)->save();
        }
        return $user;
    }
    
    public function node()
    {
        if (!$this->node) {
            $this->node = DotMesh_Model_Node::i()->load($this->node_id);
        }
        return $this->node;
    }
    
    public function uri($full=false)
    {
        return $this->node()->uri('u', $full).$this->username;
    }
    
    public function confirmRemoteSignature($signature)
    {
        $result = BUtil::fromJson(BUtil::remoteHttp('POST', $this->uri().'.json', array(
            'do' => 'confirm_signature',
            'signature' => $signature,
            'node_info' => $this->node()->info(),
        )));
        if ($result && $result['status']=='success') {
            $this->remote_signature = $signature;
        }
        return $this;
    }

    public function acceptGuest($username, $signature)
    {
        
    }
}

class DotMesh_Model_Tag extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_tag';
    
    /**
    * Shortcut to help with IDE autocompletion
    * @return DotMesh_Model_Tag
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }
    
    public function tagTimelineOrm()
    {
        $postTag = DotMesh_Model_PostTag::table();
        $postUser = DotMesh_Model_PostUser::table();
        
        $uId = (int)DotMesh_Model_User::sessionUserId();
        $tagId = (int)$this->id;
        $orm = DotMesh_Model_Post::i()->timelineOrm();
        
        $orm->where(array(
            "p.id in (select post_id from {$postTag} where tag_id={$tagId})",
            "p.is_private=0".($uId ? " or p.id in (select post_id from {$postUser} where user_id={$uId})" : ''),
            'n.is_blocked=0', // post node is not globally blocked
            'u.is_blocked=0', // post user is not globally blocked
            'p.echo_user_id is null or eu.is_blocked=0', // post is not echoed by globally blocked user
        ));
        if ($uId) {
            $orm->where(array(
                "p.node_id not in (select block_node_id from {$nodeBlock} where user_id={$uId})", // post node is not blocked by user
                "p.user_id not in (select block_user_id from {$userBlock} where user_id={$uId})", // post user is not blocked by user
            ));   
        }
        
        return $orm;
    }
    
    public static function parseUri($uri) 
    {
        $uri = str_replace('/u/', '/', $uri);
        $re = '|([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/([a-zA-Z0-9_-]+)|';
        if (!preg_match($re, $uri, $m)) {
            return false;
        }
        return array($m[1].$m[2], $m[3]);
    }
    
    public static function find($uri, $create=false) 
    {
        list($nodeUri, $tagname) = static::parseUri($uri);
        $node = DotMesh_Model_Node::i()->find($nodeUri, $create);
        //$node->is_blocked?
        $data = array('node_id'=>$node->id, 'tagname'=>$tagname);
        $tag = static::load($data);
        if (!$tag && $create) {
            $tag = static::create($data)->save();
        }
        return $tag;
    }
    
    public function node()
    {
        if (!$this->node) {
            $this->node = DotMesh_Model_Node::i()->load($this->node_id);
        }
        return $this->node;
    }
    
    public function uri($full=false)
    {
        return $this->node()->uri('t', $full).$this->tagname;
    }
}

class DotMesh_Model_UserSub extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_user_sub';
}

class DotMesh_Model_TagSub extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_tag_sub';
}

class DotMesh_Model_PostFeedback extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_post_feedback';
}

class DotMesh_Model_PostTag extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_post_tag';
}

class DotMesh_Model_PostUser extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_post_user';
}

class DotMesh_Model_NodeBlock extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_node_block';
}

class DotMesh_Model_UserBlock extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_user_block';
}

class DotMesh_Migrate extends BClass
{

}