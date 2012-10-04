<?php

if (defined('DOTMESH_CONFIGURED')) {
    BModule::defaultRunLevel(BModule::REQUESTED);
}

BModuleRegistry::i()->addModule('DotMesh', array(
    'version' => '0.1.0',
    'bootstrap' => array('callback'=>'DotMesh::bootstrap'),
    'migrate' => 'DotMesh_Migrate',
));

BConfig::i()->set('request/module_run_level/DotMesh', 'REQUIRED');

class DotMesh extends BClass
{
    public static function bootstrap()
    {
        BApp::m()->autoload();

        BFrontController::i()
            ->route('_ /noroute', 'DotMesh_Controller_Nodes.404', array(), null, false)

            ->route('GET /', 'DotMesh_Controller_Accounts.home')
            ->route('GET /:term', 'DotMesh_Controller_Nodes.catch_all')

            ->route('GET|POST /a/.action', 'DotMesh_Controller_Accounts')
            ->route('GET|POST /n/.action', 'DotMesh_Controller_Nodes')

            ->route('GET|POST|PUT|HEAD|OPTIONS /u/:username', 'DotMesh_Controller_Users.index')
            ->route('GET|POST|PUT|HEAD|OPTIONS /p/:postname', 'DotMesh_Controller_Posts.index')
            ->route('GET|POST|PUT|HEAD|OPTIONS /p/:postname/*seo-suffix', 'DotMesh_Controller_Posts.index')
            ->route('GET|POST|PUT|HEAD|OPTIONS /t/:tagname', 'DotMesh_Controller_Tags.index')

            ->route('GET|POST|PUT|HEAD|OPTIONS /n/api1.json', 'DotMesh_Controller_Nodes.api1_json')
            ->route('GET|POST|PUT|HEAD|OPTIONS /a/api1.json', 'DotMesh_Controller_Accounts.api1_json')
            ->route('GET|POST|PUT|HEAD|OPTIONS /u/:usernode/api1.json', 'DotMesh_Controller_Users.api1_json')
            ->route('GET|POST|PUT|HEAD|OPTIONS /p/:postname/api1.json', 'DotMesh_Controller_Posts.api1_json')
            ->route('GET|POST|PUT|HEAD|OPTIONS /t/:tagname/api1.json', 'DotMesh_Controller_Tags.api1_json')

            ->route('GET /n/feed.rss', 'DotMesh_Controller_Nodes.feed_rss')
            ->route('GET /a/:label/feed.rss', 'DotMesh_Controller_Accounts.feed_rss')
            ->route('GET /u/:username/feed.rss', 'DotMesh_Controller_Users.feed_rss')
            ->route('GET /p/:postname/feed.rss', 'DotMesh_Controller_Posts.feed_rss')
            ->route('GET /t/:tagname/feed.rss', 'DotMesh_Controller_Tags.feed_rss')

            ->route('^GET /u/([a-zA-Z0-9_]+)/thumb\.(png|jpg|gif)$', 'DotMesh_Controller_Users.thumb')
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
                        array('setTitleReverse', true),
                        array('css', '{DotMesh}/css/normalize.css'),
                        array('css', '{DotMesh}/css/main.css'),
                        array('css', '{DotMesh}/css/dotmesh.css'),
                        array('js', '{DotMesh}/js/head.min.js'),
                        array('js', '{DotMesh}/js/es5-shim.min.js'),
                        array('js', '//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js'),

                        array('css', '{DotMesh}/css/tipTip.css'),
                        array('js', '{DotMesh}/js/jquery.tipTip.min.js'),
                        //array('js', '{DotMesh}/js/jquery.min.js'),
                        array('js', '{DotMesh}/js/dotmesh.js'),
                    )),
                ),
                '404' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('404')),
                ),
                '503' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('503')),
                ),
                'xhr-timeline' => array(
                    array('root', 'timeline'),
                ),
                '/my' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('my-posts')),
                ),
                '/public' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('public')),
                ),
                '/setup' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('setup')),
                ),
                '/signup' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('signup')),
                ),
                '/account' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('account')),
                ),
                '/search' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('search')),
                ),
                '/thread' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('thread')),
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

    public static function folderTitle($folder=null)
    {
        $titles = array(
            'my' => 'My Timeline',
            'received' => 'Received',
            'sent' => 'Sent',
            'private' => 'Private',
            'starred' => 'Starred',
        );
        return $folder ? (!empty($titles[$folder]) ? $titles[$folder] : null) : $titles;
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
        if (!($r->method()==='GET' && $r->rawPath()==='/n/setup')) {
            if (!defined('DOTMESH_CONFIGURED')) {
                BResponse::i()->redirect(BApp::href('n/setup'));
            }
            try {
                BMigrate::i()->migrateModules(true);
            } catch (PDOException $e) {
                $this->forward('503', 'DotMesh_Controller_Nodes', array('exception'=>$e));
                return false;
            }
            if (!DotMesh_Model_Node::i()->localNode()) {
                BResponse::i()->redirect(BApp::href('n/setup'));
            }
        }
        $localNode = DotMesh_Model_Node::i()->localNode();

        BLayout::i()->view('head')->addTitle('DotMesh')->addTitle($localNode->uri());

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

/************************************************************************/

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
    public static function run()
    {

    }
}