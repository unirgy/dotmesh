<?php

/**
* This file is part of DotMesh.
*
* DotMesh is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* any later version.
*
* Foobar is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with DotMesh.  If not, see <http://www.gnu.org/licenses/>.
*
* @package DotMesh (tm)
* @link http://dotmesh.org
* @author Boris Gurvich <boris@unirgy.com>
* @copyright (c) 2012 Boris Gurvich
* @license http://www.gnu.org/licenses/gpl.txt
*/

class DotMesh_Model_Tag extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_tag';
    protected static $_cacheAuto = true;

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
            $nodeBlock = DotMesh_Model_NodeBlock::table();
            $userBlock = DotMesh_Model_UserBlocK::table();
            $orm->where(array(
                "p.node_id not in (select block_node_id from {$nodeBlock} where user_id={$uId})", // post node is not blocked by user
                "p.user_id not in (select block_user_id from {$userBlock} where user_id={$uId})", // post user is not blocked by user
            ));
        }

        return $orm;
    }

    public static function parseUri($uri)
    {
        $uri = trim($uri, '/');
        if (strpos($uri, '/')===false) { // local tag
            return array(null, $uri);
        }
        $uri = str_replace('/t/', '/', $uri);
        $re = '|([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/([a-zA-Z0-9_-]+)|';
        if (!preg_match($re, $uri, $m)) {
            return false;
        }
        return array($m[1].$m[2], $m[3]);
    }

    public static function load($id, $field=null, $cache=false)
    {
        $model = parent::load($id, $field, $cache);
        if ($model) {
            $model->cacheStore('node_id,tagname');
        }
        return $model;
    }

    public static function find($uri, $create=false)
    {
        if (!$uri) {
            throw new BException('Empty User URI');
        }
        list($nodeUri, $tagname) = static::parseUri($uri);
        $nodeHlp = DotMesh_Model_Node::i();
        if (is_array($create) && !empty($create['node_id'])) {
            $nodeId = $create['node_id'];
        } else {
            $node = $nodeUri ? $nodeHlp->find($nodeUri, $create) : $nodeHlp->localNode();
            $nodeId = $node->id;
        }
        //$node->is_blocked?
        $tag = static::load(array('node_id'=>$nodeId, 'tagname'=>$tagname));
        if (!$tag && $create) {
            $create = (array)$create;
            $create['node_id'] = $nodeId;
            $create['tagname'] = $tagname;
            $tag = static::create($create)->save();
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

    public function subscribersCnt()
    {
        $cnt = DotMesh_Model_TagSub::i()->orm()
            ->where('pub_tag_id', $this->id)->select('(count(*))', 'value')->find_one();
        return $cnt ? $cnt->value : 0;
    }

    public function postsCnt()
    {
        $cnt = DotMesh_Model_Post::i()->orm('p')
            ->join('DotMesh_Model_PostTag', array('pt.post_id','=','p.id'), 'pt')
            ->where('pt.tag_id', $this->id)->where('p.is_private', 0)->select('(count(*))', 'value')->find_one();
        return $cnt ? $cnt->value : 0;
    }

    public static function trendingTags()
    {
        return array();
    }
}
