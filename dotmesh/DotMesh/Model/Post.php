<?php

class DotMesh_Model_Post extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_post';
    protected static $_cacheAuto = true;
    
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
            ->left_outer_join('DotMesh_Model_Node', array('en.id','=','eu.node_id'), 'en')
            ->order_by_desc('p.create_dt')
        ;
        if (($uId = (int)DotMesh_Model_User::sessionUserId())) {
            $orm->left_outer_join('DotMesh_Model_PostFeedback', "pf.post_id=p.id and pf.user_id={$uId}", 'pf')
                ->select(array('pf.star', 'user_score'=>'pf.score', 'pf.report'));
        }
        return $orm;
    }
    
    public static function fetchTimeline($orm)
    {
        $orm->select(array(
            'node_uri' => 'n.uri',
            'node_is_local' => 'n.is_local',
            'node_is_https' => 'n.is_https',
            'node_is_rewrite' => 'n.is_rewrite',
            
            'user_username' => 'u.username',
            'user_firstname' => 'u.firstname',
            'user_lastname' => 'u.lastname',
            'user_thumb_provider' => 'u.thumb_provider',
            'user_thumb_filename' => 'u.thumb_filename',
            
            'echo_user_node_id' => 'eu.node_id',
            'echo_user_username' => 'eu.username',
            'echo_user_firstname' => 'eu.firstname',
            'echo_user_lastname' => 'eu.lastname',
            'echo_user_thumb_provider' => 'eu.thumb_provider',
            'echo_user_thumb_filename' => 'eu.thumb_filename',
            
            'echo_node_id' => 'eu.node_id',
            'echo_node_uri' => 'en.uri',
            'echo_node_is_local' => 'en.is_local',
            'echo_node_is_https' => 'en.is_https',
            'echo_node_is_rewrite' => 'en.is_rewrite',
        ));
        
        $timeline = $orm->find_many();
        
        foreach ($timeline as $p) {
            $node = $p->node(array(
                'id' => $p->node_id, 
                'uri' => $p->node_uri,
                'is_local' => $p->node_is_local, 
                'is_https' => $p->node_is_https, 
                'is_rewrite' => $p->node_is_rewrite,
            ));
            $user = $p->user(array(
                'id' => $p->user_id,
                'node_id' => $p->node_id,
                'username' => $p->user_username,
                'firstname' => $p->user_firstname,
                'lastname' => $p->user_lastname,
                'thumb_provider' => $p->user_thumb_provider,
                'thumb_filename' => $p->user_thumb_filename,
            ));
            $user->node = $node;
            if ($p->echo_user_id) {
                $echoUser = $p->echoUser(array(
                    'id' => $p->echo_user_id,
                    'node_id' => $p->echo_user_node_id,
                    'username' => $p->echo_user_username,
                    'firstname' => $p->echo_user_firstname,
                    'lastname' => $p->echo_user_lastname,
                    'echo_thumb_provider' => $p->echo_user_thumb_provider,
                    'echo_thumb_filename' => $p->echo_user_thumb_filename,
                ));
                $echoNode = $echoUser->node(array(
                    'id' => $p->echo_node_id, 
                    'uri' => $p->echo_node_uri,
                    'is_local' => $p->echo_node_is_local, 
                    'is_https' => $p->echo_node_is_https, 
                    'is_rewrite' => $p->echo_node_is_rewrite,
                ));
            }
        }
        return $timeline;
    }
    
    public function homeTimelineOrm()
    {
        $orm = static::timelineOrm();
        $orm->where('p.is_private', 0);
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
    
    public function threadTimelineOrm()
    {
        $postUser = DotMesh_Model_PostUser::table();
        
        $pubUserId = $this->user_id;
        $threadId = $this->thread_id ? $this->thread_id : $this->id;
        $uId = (int)DotMesh_Model_User::sessionUserId();
        $orm = static::timelineOrm();
        
        $orm->where(array('AND'=>array(
            "p.id={$threadId} or p.thread_id={$threadId}",
            "p.user_id={$pubUserId} or p.echo_user_id={$pubUserId}",
            "p.is_private=0".($uId ? " or p.id in (select post_id from {$postUser} where user_id={$uId})" : ''),
        )));
        
        return $orm;
    }
    
    public function searchTimelineOrm($q)
    {
        $postUser = DotMesh_Model_PostUser::table();
        
        $uId = (int)DotMesh_Model_User::sessionUserId();
        $orm = DotMesh_Model_Post::i()->timelineOrm();
        
        $orm->where(array('AND'=>array(
            array('p.contents like ?', '%'.$q.'%'),
            "p.is_private=0".($uId ? " or p.id in (select post_id from {$postUser} where user_id={$uId})" : ''),
        )));
        
        return $orm;
    }
        
    public static function nextSeqName()
    {
        $node = DotMesh_Model_Node::i()->localNode();
        $maxPost = static::orm()->where('node_id', $node->id)->select('(max(postname))', 'max_postname')->find_one();
        if (!$maxPost) {
            return '1';
        }
        $next = $maxPost->max_postname;
        for ($i=0; $i<10; $i++) {
            $next = BUtil::nextStringValue($next);
            if (!$node->user($next) && !$node->tag($next)) {
                return $next;
            }
        }
        return $next;
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
        
        $localNode = DotMesh_Model_Node::localNode();
        $data = array(
            'node_id' => $localNode->id,
            'postname' => static::nextSeqName(),
            'parent_post_id' => !empty($r['parent_post_id']) ? $r['parent_post_id'] : null,
            'user_id' => DotMesh_Model_User::sessionUserId(),
            'preview' => $preview,
            'contents' => $r['contents'],
            'is_private' => !empty($r['is_private']),
        );
        if (!empty($r['inreplyto'])) {
            $threadPost = $localNode->post($r['inreplyto']);
            if ($threadPost) {
                //$data['thread_post'] = $threadPost;
                $data['thread_id'] = $threadPost->thread_id ? $threadPost->thread_id : $threadPost->id;
            }
        }
        
        $post = static::create($data)->save();
        
        $post->collectUsersAndTags();
        
        return $post;
    }
    
    public static function receiveRemotePost($r)
    {
        $post = static::create(array(
            //'node_id' => $r->node
        ))->save();
        return $post;
    }
    
    public static function load($id, $field=null, $cache=false)
    {
        $model = parent::load($id, $field, $cache);
        if ($model) $model->cacheStore('node_id,postname');
        return $model;
    }
    
    public function uri($full=null)
    {
        $node = DotMesh_Model_Node::i()->load($this->node_id);
        return $node->uri('p', $full).$this->postname;
    }
    
    public function node($data=null, $reset=false)
    {
        if (!$this->node || $reset) {
            $hlp = DotMesh_Model_Node::i();
            if ($data) {
                $this->node = $hlp->create($data);
            } else {
                $this->node = $hlp->load($this->node_id);
            }
        }
        return $this->node;
    }
    
    public function user($data=null, $reset=false)
    {
        if (!$this->user || $reset) {
            $hlp = DotMesh_Model_User::i();
            if ($data) {
                $this->user = $hlp->create($data);
            } else {
                $this->user = $hlp->load($this->user_id);   
            }
        }
        return $this->user;
    }
    
    public function echoUser($data=null, $reset=false)
    {
        if (!$this->echo_user || $reset) {
            $hlp = DotMesh_Model_User::i();
            if ($data) {
                $this->echo_user = $hlp->create($data);
            } else {
                $this->echo_user = $hlp->load($this->echo_user_id);
            }
        }   
        return $this->echo_user;
    }
    
    public function collectUsersAndTags($contents=null)
    {
        if (is_null($contents)) {
            $contents = $this->contents;
        }
        $re = '`(?:^|\s)([+&^])(?:([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/)?([a-zA-Z0-9_-]+)`';
        if (preg_match_all($re, $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $uri = $m[2].'/'.$m[4];
                switch ($m[1]) {
                case '&': case '+':
                    $user = DotMesh_Model_User::i()->find($uri, true);
                    DotMesh_Model_PostUser::i()->create(array('post_id'=>$this->id, 'user_id'=>$user->id))->save();
                    break;
                case '^':
                    $tag = DotMesh_Model_Tag::i()->find($uri, true);
                    DotMesh_Model_PostTag::i()->create(array('post_id'=>$this->id, 'tag_id'=>$tag->id))->save();
                    break;
                }
            }
        }
        return $this;
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
            $uri = $m[3].$m[4].'/'.$m[5];
            switch ($m[2]) {
            case '&': case '+':
                $user = DotMesh_Model_User::i()->find($uri, true);
                //return $m[1].$m[2].'<a href="'.$user->uri(true).'">'.$uri.'</a>';
                return "{$m[1]}{$m[2]}<a href=\"{$user->uri(true)}\">{$uri}</a>";
            case '^': 
                $tag = DotMesh_Model_Tag::i()->find($uri, true);
                //return $m[1].$m[2].'<a href="'.$tag->uri(true).'">'.$uri.'</a>';
                return "{$m[1]}{$m[2]}<a href=\"{$tag->uri(true)}\">{$uri}</a>";
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
            return "{$m[1]}<a href=\"{$uri}\" class=\"image-link\" data-src=\"{$src}\">{$label}</a>";
        }, $contents);
    }
    
    protected static function _formatYouTubeLinks(&$contents)
    {
        $re = '`(^|\s)(https?://)?(youtu\.be/|(www\.)?youtube\.com/watch\?v=)([a-zA-Z0-9_.-]+)(\S*)`';
        $contents = preg_replace_callback($re, function($m) {
            $uri = htmlspecialchars($m[2].$m[3].$m[5]);
            $label = 'youtu.be/'.$m[5];
            $src = $m[2].'www.youtube.com/embed/'.$m[5];
            return "{$m[1]}<a href=\"{$uri}\" class=\"youtube-link\" data-src=\"{$src}\">{$label}</a>";
        }, $contents);
    }
    
    public static function formatContentsHtml($contents, $preview=false)
    {
        static::_formatTwitterLinks($contents);
        static::_formatDotMeshLinks($contents);
        static::_formatImageLinks($contents);
        static::_formatYouTubeLinks($contents);
        
        BPubSub::i()->fire(__METHOD__, array('contents'=>&$contents, 'preview'=>$preview));
        
        return $contents;
    }
    
    public function previewHtml()
    {
        return static::formatContentsHtml($this->preview, true);
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

