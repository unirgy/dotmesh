<?php

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
    
    public function threadTimelineOrm()
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

