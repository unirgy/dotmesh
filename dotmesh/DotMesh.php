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
            ->route('GET|POST /u/:username', 'DotMesh_Controller_Users.index')
            ->route('GET|POST /p/:postname', 'DotMesh_Controller_Posts.index')
            ->route('GET|POST /p/:postname/*seo-suffix', 'DotMesh_Controller_Posts.index')
            ->route('GET|POST /t/:tagname', 'DotMesh_Controller_Tags.index')

            ->route('GET|POST|PUT|HEAD|OPTIONS /n/api1.json', 'DotMesh_Controller_Nodes.api1_json')
            ->route('GET|POST|PUT|HEAD|OPTIONS /a/api1.json', 'DotMesh_Controller_Accounts.api1_json')
            ->route('GET|POST|PUT|DELETE|HEAD|OPTIONS /u/:usernode/api1.json', 'DotMesh_Controller_Users.api1_json')
            ->route('GET|POST|PUT|DELETE|HEAD|OPTIONS /p/:postname/api1.json', 'DotMesh_Controller_Posts.api1_json')
            ->route('GET|POST|PUT|DELETE|HEAD|OPTIONS /t/:tagname/api1.json', 'DotMesh_Controller_Tags.api1_json')

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
                        //array('js', '{DotMesh}/js/jquery.min.js'),

                        array('css', '{DotMesh}/css/tipTip.css'),
                        array('js', '{DotMesh}/js/jquery.tipTip.min.js'),

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
                '/password_reset' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('password-reset')),
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
                '/pub_users' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('user-list')),
                ),
                '/sub_users' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('user-list')),
                ),
                '/pub_tags' => array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('tag-list')),
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
        if ($r->rawPath()!=='/n/setup') {
            if (!defined('DOTMESH_CONFIGURED')) {
                BResponse::i()->redirect(BApp::href('n/setup'));
            }
            /*
            try {
                BMigrate::i()->migrateModules(true);
            } catch (PDOException $e) {
                $this->forward('503', 'DotMesh_Controller_Nodes', array('exception'=>$e));
                return false;
            }
            */
            if (!DotMesh_Model_Node::i()->localNode()) {
#echo 1; exit;
                BResponse::i()->redirect(BApp::href('n/setup'));
            }
        }
        $localNode = DotMesh_Model_Node::i()->localNode();
        if ($localNode) {
            BLayout::i()->view('head')->setTitle('DotMesh')->addTitle($localNode->uri());
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

/************************************************************************/

class DotMesh_Util extends BClass
{
    public static function formatHtml($contents, $type=null)
    {
        static::_formatTwitterLinks($contents);
        static::_formatDotMeshLinks($contents);
        static::_formatImageLinks($contents);
        static::_formatYouTubeLinks($contents);
        static::_formatWebLinks($contents);

        if (DotMesh_Model_Node::i()->config('contents_default_process')) {
            $contents = nl2br($contents);
        }

        BPubSub::i()->fire(__METHOD__, array('contents'=>&$contents, 'type'=>$type));

        return $contents;
    }

    protected static function _formatTwitterLinks(&$contents)
    {
        $re = '`(^|\s)([@#])([a-zA-Z0-9_]+)`';
        $contents = preg_replace_callback($re, function($m) {
            $str = $m[2].$m[3];
            switch ($m[2]) {
            case '@':
                return $m[1].'<a href="https://twitter.com/'.urlencode($m[3]).'">'.htmlspecialchars($str).'</a>';
            case '#':
                return $m[1].'<a href="https://twitter.com/#!/search/?q='.urlencode($str).'">'.htmlspecialchars($str).'</a>';
            }
        }, $contents);
    }

    protected static function _formatDotMeshLinks(&$contents)
    {
        $re = '`(^|\s)([&+^])(?:([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/)?([a-zA-Z0-9_-]+)`';
        $contents = preg_replace_callback($re, function($m) {
            $uri = trim($m[3].$m[4].'/'.$m[5], '/');
            switch ($m[2]) {
            case '&': case '+':
                $user = DotMesh_Model_User::i()->find($uri);
                if ($user) {
                    $fullUri = $user->uri(true);
                } elseif ($m[3]) {
                    $node = DotMesh_Model_Node::i()->find($m[3].$m[4]);
                    if ($node) {
                        $fullUri = $node->uri('u', true).$m[5];
                    } else {
                        $fullUri = 'http://'.$uri;
                    }
                } else {
                    $node = DotMesh_Model_Node::i()->localNode();
                    $uri = $node->uri(false).'/'.$m[5];
                    $fullUri = $node->uri('u', true).$m[5];
                }
                return "{$m[1]}{$m[2]}<a href=\"{$fullUri}\">{$uri}</a>";
            case '^':
                $tag = DotMesh_Model_Tag::i()->find($uri);
                if ($tag) {
                    $fullUri = $tag->uri(true);
                } elseif ($m[3]) {
                    $node = DotMesh_Model_Node::i()->find($m[3].$m[4]);
                    if ($node) {
                        $fullUri = $node->uri('t', true).$m[5];
                    } else {
                        $fullUri = 'http://'.$uri;
                    }
                } else {
                    $node = DotMesh_Model_Node::i()->localNode();
                    $uri = $node->uri(false).'/'.$m[5];
                    $fullUri = $node->uri('t', true).$m[5];
                }
                return "{$m[1]}{$m[2]}<a href=\"{$fullUri}\">{$uri}</a>";
            }
        }, $contents);
    }

    protected static function _formatImageLinks(&$contents)
    {
        $re = '`(^|\s)(https?://)?([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S+\.)(png|jpg|gif)`';
        $contents = preg_replace_callback($re, function($m) {
            $uri = $m[2].$m[3].$m[4].$m[5];
            if (strlen($uri)<=30) {
                $label = htmlspecialchars($uri);
            } else {
                $parsed = parse_url($uri);
                $filename = pathinfo($parsed['path'], PATHINFO_BASENAME);
                $label = htmlspecialchars($parsed['host'].'/.../'.$filename);
            }
            $uri = htmlspecialchars($uri);
            $src = htmlspecialchars($m[2].$m[3].$m[4].$m[5]); // account for imgur and other cloud image services
            return "{$m[1]}<a href=\"{$uri}\" class=\"image-link\" data-src=\"{$src}\" target=\"_blank\">{$label}</a>";
        }, $contents);
    }

    protected static function _formatYouTubeLinks(&$contents)
    {
        $re = '`(^|\s)(https?://)?(youtu\.be/|(www\.)?youtube\.com/watch\?v=)([a-zA-Z0-9_.-]+)(\S*)`';
        $contents = preg_replace_callback($re, function($m) {
            $uri = htmlspecialchars(($m[2] ? $m[2] : 'http://').$m[3].$m[5]);
            $label = 'youtu.be/'.$m[5];
            $src = ($m[2] ? $m[2] : 'http://').'www.youtube.com/embed/'.$m[5];
            return "{$m[1]}<a href=\"{$uri}\" class=\"youtube-link\" data-src=\"{$src}\" target=\"_blank\">{$label}</a>";
        }, $contents);
    }

    protected static function _formatWebLinks(&$contents)
    {
        $re = '`(^|\s)(https?://)?([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S+)`';
        $contents = preg_replace_callback($re, function($m) {
            $uri = htmlspecialchars(($m[2] ? $m[2] : 'http://').$m[3].$m[4]);
            $label = htmlspecialchars($m[2].$m[3].$m[4]);
            return "{$m[1]}<a href=\"{$uri}\" class=\"web-link\" target=\"_blank\">{$label}</a>";
        }, $contents);
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

class DotMesh_Model_PostFile extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_post_file';
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
