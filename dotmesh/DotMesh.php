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
            
            ->route('GET /n/id.json', 'DotMesh_Controller_Node.id_json')
            
            ->route('GET|POST /a/.action', 'DotMesh_Controller_Account')
            
            ->route('GET|POST|PUT|HEAD|OPTIONS /u/:user', 'DotMesh_Controller_User.index')
            ->route('GET|POST|PUT|HEAD|OPTIONS /p/:post', 'DotMesh_Controller_Post.index')
            ->route('GET|POST|PUT|HEAD|OPTIONS /t/:tag', 'DotMesh_Controller_Tag.index')
            
            ->route('^(GET|POST|PUT|HEAD|OPTIONS) /u/([a-zA-Z0-9_]+).json$', 'DotMesh_Controller_User.json')
            ->route('^(GET|POST|PUT|HEAD|OPTIONS) /p/([a-zA-Z0-9_]+).json$', 'DotMesh_Controller_Post.json')
            ->route('^(GET|POST|PUT|HEAD|OPTIONS) /t/([a-zA-Z0-9_]+).json$', 'DotMesh_Controller_Tag.json')
            
            ->route('^GET /u/([a-zA-Z0-9_]+).rss$', 'DotMesh_Controller_User.rss')
            ->route('^GET /p/([a-zA-Z0-9_]+).rss$', 'DotMesh_Controller_Post.rss')
            ->route('^GET /t/([a-zA-Z0-9_]+).rss$', 'DotMesh_Controller_Tag.rss')
            
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
        if (!DotMesh_Model_Node::i()->localNode() && BRequest::i()->rawPath()!=='/a/setup') {
            BResponse::i()->redirect(BApp::href('a/setup'));
        }
        return true;
    }
    
    public function afterDispatch()
    {
        BResponse::i()->output();
    }   
}

class DotMesh_Controller extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        BLayout::i()->applyLayout('/');
        BResponse::i()->output();
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
        BLayout::i()->applyLayout('/setup');
        BResponse::i()->output();
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
        BResponse::i()->output();
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
        BResponse::i()->output();
    }
    
    public function action_password_recover__POST()
    {
        
        BResponse::i()->redirect(BApp::href());
    }
    
    public function action_password_reset()
    {
        BLayout::i()->applyLayout('/password_reset');
        BResponse::i()->output();
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
    public function action_id_json()
    {
        
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
    
    public static function localNode()
    {
        if (is_null(static::$_localNode)) {
            static::$_localNode = static::load(1);
        }
        return static::$_localNode;
    }
    
    public static function setup($form)
    {
        
    }
}

class DotMesh_Model_User extends BModelUser
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_user';
}

class DotMesh_Model_Post extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_post';
}

class DotMesh_Model_Tag extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_tag';
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

class DotMesh_Migrate extends BClass
{
    
}