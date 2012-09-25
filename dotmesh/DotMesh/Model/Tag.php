<?php

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
