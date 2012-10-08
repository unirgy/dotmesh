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
        ;
        if (($uId = (int)DotMesh_Model_User::sessionUserId())) {
            $fbTable = DotMesh_Model_PostFeedback::table();
            $orm->left_outer_join($fbTable, "pf.post_id=p.id and pf.user_id={$uId}", 'pf')
                ->select(array('pf.echo', 'pf.star', 'pf.vote_up', 'pf.vote_down', 'pf.flag'));
        }

        return $orm;
    }

    public static function fetchTimeline($orm)
    {
        $fbTable = DotMesh_Model_PostFeedback::table();
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
            'user_thumb_uri' => 'u.thumb_uri',

            'echo_user_node_id' => 'eu.node_id',
            'echo_user_username' => 'eu.username',
            'echo_user_firstname' => 'eu.firstname',
            'echo_user_lastname' => 'eu.lastname',
            'echo_user_thumb_provider' => 'eu.thumb_provider',
            'echo_user_thumb_filename' => 'eu.thumb_filename',
            'echo_user_thumb_uri' => 'eu.thumb_uri',

            'echo_node_id' => 'eu.node_id',
            'echo_node_uri' => 'en.uri',
            'echo_node_is_local' => 'en.is_local',
            'echo_node_is_https' => 'en.is_https',
            'echo_node_is_rewrite' => 'en.is_rewrite',

            'feedback_totals' => "(select concat(sum(ifnull(echo,0)),';',sum(ifnull(star,0)),';',sum(ifnull(flag,0)),
                ';',sum(ifnull(vote_up,0)),';',sum(ifnull(vote_down,0))) from {$fbTable} where post_id=p.id)",
        ));

        $pageNum = max(0, BRequest::i()->get('p')-1);
        $pageSize = BConfig::i()->get('modules/DotMesh/timeline_page_size');
        $sort = BRequest::i()->get('s');
        if ($sort) {
            switch ($sort) {
            case 'hot':
                $hotDate = date('Y-m-d', time()-86400*7); // voted up past 7 days
                $sort = "(select sum(ifnull(vote_up,0)) from {$fbTable} where post_id=p.id and vote_up_dt>'{$hotDate}')";
                break;
            case 'best':
                $sort = "(select sum(ifnull(vote_up,0)-ifnull(vote_down,0)) from {$fbTable} where post_id=p.id)";
                break;
            case 'worst':
                $sort = "(select sum(ifnull(vote_down,0)-ifnull(vote_up,0)) from {$fbTable} where post_id=p.id)";
                break;
            case 'controversial':
                $sort = "(select sum(ifnull(vote_up,0)+ifnull(vote_down,0)) from {$fbTable} where post_id=p.id)";
                break;
            default:
                $sort = '';
            }
            if ($sort) {
                $orm->order_by_desc($sort);
            }
        }
        $orm->offset($pageNum*$pageSize)->limit($pageSize)->order_by_desc('p.is_pinned')->order_by_desc('p.create_dt');
        $rows = (array)$orm->find_many();

        foreach ($rows as $p) {
            $fbTotals = explode(';', $p->feedback_totals);
            $p->set(array(
                'total_echos' => !empty($fbTotals[0]) ? $fbTotals[0] : '',
                'total_stars' => !empty($fbTotals[1]) ? $fbTotals[1] : '',
                'total_flags' => !empty($fbTotals[2]) ? $fbTotals[2] : '',
                'total_vote_up' => !empty($fbTotals[3]) ? $fbTotals[3] : '',
                'total_vote_down' => !empty($fbTotals[4]) ? $fbTotals[4] : '',
            ));
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
                'thumb_uri' => $p->user_thumb_uri,
            ));
            $user->node = $node;
            if ($p->echo_user_id) {
                $echoUser = $p->echoUser(array(
                    'id' => $p->echo_user_id,
                    'node_id' => $p->echo_user_node_id,
                    'username' => $p->echo_user_username,
                    'firstname' => $p->echo_user_firstname,
                    'lastname' => $p->echo_user_lastname,
                    'thumb_provider' => $p->echo_user_thumb_provider,
                    'thumb_filename' => $p->echo_user_thumb_filename,
                    'thumb_uri' => $p->echo_user_thumb_uri,
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

        return array('rows'=>$rows, 'is_last_page'=>sizeof($rows)<$pageSize);
    }

    public function publicTimelineOrm()
    {
        $orm = static::timelineOrm();
        $localNode = DotMesh_Model_Node::i()->localNode();
        $orm->where('p.is_private', 0)
            ->where('p.node_id', $localNode->id);
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
            "p.is_private=0".($uId ? " or p.user_id={$uId} or p.id in (select post_id from {$postUser} where user_id={$uId})" : ''),
        )))->select("(p.id={$threadId})", 'expanded');

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

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->create_dt) $this->create_dt = BDb::now();
        $this->update_dt = BDb::now();
        return true;
    }

    public static function nextSeqName()
    {
        $node = DotMesh_Model_Node::i()->localNode();
        $next = $node->last_postname;
        if (!$next) {
            return '1';
        }
        for ($i=0; $i<10; $i++) {
            $next = BUtil::nextStringValue($next, '123456789abdefghijklmnopqrstuvwxyz');
            if ($node->post($next)) {
                $i--;
                continue;
            } elseif (!$node->user($next) && !$node->tag($next)) {
                return $next;
            }
        }
        return $next;
    }

    public static function submitNewPost($r)
    {
        $contentLines = explode("\n", trim($r['contents']));
        $preview = sizeof($contentLines)>1 ? $contentLines[0] : substr($r['contents'], 0, 140);

        $localNode = DotMesh_Model_Node::i()->localNode();
        $sessUser = DotMesh_Model_User::i()->sessionUser();

        $next = (string)$localNode->last_postname;
        for ($i=0; $i<10; $i++) {
            $next = BUtil::nextStringValue($next);
            if ($localNode->post($next)) {
                $i--;
                continue;
            } elseif (!$localNode->user($next) && !$localNode->tag($next)) {
                break;
            }
        }
        $data = array(
            'node_id' => $localNode->id,
            'postname' => $next,
            'parent_post_id' => !empty($r['parent_post_id']) ? $r['parent_post_id'] : null,
            'user_id' => $sessUser->id,
            'preview' => $preview,
            'contents' => $r['contents'],
            'is_private' => !empty($r['is_private']),
            'is_pinned' => $sessUser->is_admin && !empty($r['is_pinned']),
        );
        if (!empty($r['inreplyto'])) {
            $threadPost = $localNode->post($r['inreplyto']);
            if ($threadPost) {
                //$data['thread_post'] = $threadPost;
                $data['thread_id'] = $threadPost->thread_id ? $threadPost->thread_id : $threadPost->id;
            }
        }

        BPubSub::i()->fire(__METHOD__.'.before', array('request'=>$r, 'data'=>&$data));

        $post = static::create($data)->save();
        $localNode->set('last_postname', $post->postname)->save();

        $post->collectUsersAndTags();
        $post->distribute(true);

        BPubSub::i()->fire(__METHOD__.'.after', array('post'=>$post));

        return $post;
    }

    public static function receiveRemotePost($data)
    {
        BPubSub::i()->fire(__METHOD__.'.before', array('request'=>&$data));

        $post = static::create($data)->save();

        $post->collectUsersAndTags();
        $post->distribute();

        BPubSub::i()->fire(__METHOD__.'.after', array('request'=>$data, 'post'=>$post));

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

    public static function parseUri($uri)
    {
        $uri = trim($uri, '/');
        if (strpos($uri, '/')===false) { // local user
            return array(null, $uri);
        }
        $uri = str_replace('/p/', '/', $uri);
        $re = '`([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/([a-zA-Z0-9]+)`';
        if (!preg_match($re, $uri, $m)) {
            return false;
        }
        return array($m[1].$m[2], $m[3]);
    }

    public static function find($uri, $create=false)
    {
        list($nodeUri, $postname) = static::parseUri($uri);
        $nodeHlp = DotMesh_Model_Node::i();
        if (is_array($create) && !empty($create['node_id'])) {
            $nodeId = $create['node_id'];
        } else {
            $node = $nodeUri ? $nodeHlp->find($nodeUri, $create) : $nodeHlp->localNode();
            $nodeId = $node->id;
        }
        //$node->is_blocked?
        $post = static::load(array('node_id'=>$nodeId, 'postname'=>$postname));
        if (!$post && $create) {
            $create = (array)$create;
            $create['node_id'] = $nodeId;
            $create['postname'] = $postname;
            unset($create['id']);
            $post = static::create($create)->save();
        }
        return $post;
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
            $dups = array();
            foreach ($matches as $m) {
                $uri = $m[2].'/'.$m[4];
                switch ($m[1]) {
                case '&': case '+':
                    $user = DotMesh_Model_User::i()->find($uri, true);
                    $key = "u-{$this->id}-{$user->id}";
                    if (empty($dups[$key])) {
                        DotMesh_Model_PostUser::i()->create(array('post_id'=>$this->id, 'user_id'=>$user->id))->save();
                        $dups[$key] = 1;
                    }
                    break;
                case '^':
                    $tag = DotMesh_Model_Tag::i()->find($uri, true);
                    $key = "t-{$this->id}-{$tag->id}";
                    if (empty($dups[$key])) {
                        DotMesh_Model_PostTag::i()->create(array('post_id'=>$this->id, 'tag_id'=>$tag->id))->save();
                        $dups[$key] = 1;
                    }
                    break;
                }
            }
        }
        return $this;
    }

    public function previewHtml()
    {
        return DotMesh_Util::i()->formatHtml($this->preview, 'preview');
    }

    public function contentsHtml()
    {
        return DotMesh_Util::i()->formatHtml($this->contents, 'contents');
    }

    public function mentionedUsers()
    {
        return DotMesh_Model_User::i()->orm('u')
            ->join('DotMesh_Model_PostUser', array('pu.user_id','=','u.id'), 'pu')
            ->where('pu.post_id', $this->id)
            ->find_many();
    }

    public function normalizePreviewUsersTags()
    {
        //return $this->preview;
        $re = '`(^|\s)([&+^])(?:([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/)?([a-zA-Z0-9_-]+)`';
        $localUri = DotMesh_Model_Node::i()->localNode()->uri();
        $preview = preg_replace_callback($re, function($m) use($localUri) {
            return $m[3] ? $m[0] : $m[1].$m[2].$localUri.'/'.$m[5];
        }, $this->preview);
        return $preview;
    }

    public function submitFeedback($data, $userId=null)
    {
        if ($value<0 || $value>1) {
            throw new BException('Invalid feedback value: '.$value);
        }
        if (is_null($userId)) {
            $userId = DotMesh_Model_User::sessionUserId();
        }
        if (!$userId) {
            throw new BException('Invalid user');
        }
        $data = BUtil::maskFields($data, 'echo,star,flag,vote_up,vote_down');
        $where = array('post_id'=>$this->id, 'user_id'=>$userId);

        if ($type=='vote_up') {
            $data['vote_down'] = 0;
            $data['vote_up_dt'] = BDb::now();
        }
        if ($type=='vote_down') {
            $data['vote_up'] = 0;
        }
        $fb = DotMesh_Model_PostFeedback::i()->load($where);
        if (!$fb) {
            $fb = DotMesh_Model_PostFeedback::i()->create($where)->set($data)->save();
        } else {
            $fb->set($data);
            DotMesh_Model_PostFeedback::i()->update_many($data, $where);
        }
        $this->feedback = $fb;
        return $this;
    }

    public function receiveRemoteFeedback($data, $userId)
    {
        $where = array('post_id'=>$this->id, 'user_id'=>$userId);
        $fb = DotMesh_Model_PostFeedback::i()->load($where);
        if (!$fb) {
            $fb = DotMesh_Model_PostFeedback::i()->create($where)->set($data)->save();
        } else {
            $fb->set($data);
            DotMesh_Model_PostFeedback::i()->update_many($data, $where);
        }
    }

    public function distribute($sendRemote=false)
    {
        $usersToSend = array();

        if ($this->user()->node()->is_local) {
            $usersToSend[$this->user_id] = $this->user();
        }
        if ($this->echo_user_id && $this->echoUser()->node()->is_local) {
            $usersToSend[$this->echo_user_id] = $this->echoUser();
        }

        $remoteNodes = array();

        $users = DotMesh_Model_User::i()->orm('u')
            ->join('DotMesh_Model_PostUser', array('pu.user_id','=','u.id'), 'pu')
            ->where('pu.post_id', $this->id)
            ->find_many();
        foreach ((array)$users as $user) {
            $node = $user->node();
            if ($node->is_local) {
                if ($sendRemote) {
                    $usersToSend[$user->id] = $user;
                }
//TODO: send email notifications
            } elseif ($sendRemote) {
                $remoteNodes[$node->id] = $node;
            }
        }

        if (!$this->is_private) {
            $tags = DotMesh_Model_Tag::i()->orm('t')
                ->join('DotMesh_Model_PostTag', array('pt.tag_id','=','t.id'), 'pt')
                ->where('pt.post_id', $this->id)
                ->find_many();
            foreach ((array)$tags as $tag) {
                $node = $tag->node();
                if ($node->is_local) {
//TODO: send email notifications
                } elseif ($sendRemote) {
                    $remoteNodes[$node->id] = $node;
                }
            }
        }

        if (!empty($remoteNodes)) {
            foreach ($remoteNodes as $node) {
                $node->apiClient(array('users'=>$usersToSend, 'posts'=>array($this)));
            }
        }

        return $this;
    }

    public static function toRss($channel, $posts)
    {
        $channel['items'] = array();
        foreach ($posts as $post) {
            $descr = "By: {$post->user()->uri()}<br/>{$post->contents}";
            $channel['items'][] = array(
                'title' => $post->preview,
                'description' => $descr,
                'pubDate' => $post->create_dt,
                'link' => $post->uri(true),
            );
        }
        return BUtil::toRss($channel);
    }
}

