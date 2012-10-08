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

class DotMesh_Model_User extends BModelUser
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_user';
    protected static $_cacheAuto = true;

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return DotMesh_Model_User
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    public static function signup($r, $fields='')
    {
        $r = (array)$r;
        if (empty($r['username']) || empty($r['email'])
            || empty($r['firstname']) || empty($r['lastname'])
            || empty($r['password']) || empty($r['password_confirm'])
            || $r['password']!=$r['password_confirm']
        ) {
            throw new Exception('Incomplete or invalid form data.');
        }

        $node = DotMesh_Model_Node::i()->localNode();
        $r['secret_key'] = BUtil::randomString(64);
        $user = $node->user($r['username']);
        if ($user) {
            if (!$user->password_hash) {
                $r = BUtil::maskFields($r, 'email,firstname,lastname,secret_key');
                $user->set($r)->setPassword($r['password'])->save();
            } else {
                throw new Exception('User already registered');
            }
        } else {
            $fields = ($fields ? ',' : '').'username,email,password,secret_key,firstname,lastname,thumb_provider';
            $r = BUtil::maskFields($r, $fields);
            $r['node_id'] = $node->id;
            $user = static::create($r)->save();
            if (($view = BLayout::i()->view('email/user-new-user'))) {
                $view->set('user', $user)->email();
            }
            if (($view = BLayout::i()->view('email/admin-new-user'))) {
                $view->set('user', $user)->email();
            }
        }
        return $user;
    }

    static public function authenticate($username, $password)
    {
        /** @var FCom_Admin_Model_User */
        $user = static::orm()
            ->where('node_id', DotMesh_Model_Node::i()->localNode()->id)
            ->where(array('OR'=>array('username'=>$username, 'email'=>$username)))
            ->find_one();
        if (!$user || !$user->validatePassword($password)) {
            return false;
        }
        return $user;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        $this->set('thumb_provider', 'gravatar', null);
        $this->set('preferences_data', BUtil::toJson((object)$this->preferences));
        return true;
    }

    public function afterLoad()
    {
        parent::afterLoad();
        if ($this->preferences_data) {
            $this->set('preferences', (array)BUtil::fromJson($this->preferences_data));
        }
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

    public static function myTimelineOrm($uId=null)
    {
        $nodeBlock = DotMesh_Model_NodeBlock::table();
        $userBlock = DotMesh_Model_UserBlock::table();
        $postUser = DotMesh_Model_PostUser::table();
        $postTag = DotMesh_Model_PostTag::table();
        $userSub = DotMesh_Model_UserSub::table();
        $tagSub = DotMesh_Model_TagSub::table();

        if (!$uId) {
            $uId = (int)static::sessionUserId();
        }
        $orm = DotMesh_Model_Post::i()->timelineOrm();

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

    public function myFolderTimelineOrm($folder, $uId=null)
    {
        $postUser = DotMesh_Model_PostUser::table();
        $postFeedback = DotMesh_Model_PostFeedback::table();

        if (!$uId) {
            $uId = (int)static::sessionUserId();
        }
        $orm = DotMesh_Model_Post::i()->timelineOrm();

        switch ($folder) {
        case 'received':
            $orm->where(array(
                "p.id in (select post_id from {$postUser} where user_id={$uId})"
            ));
            break;

        case 'sent':
            $orm->where(array(
                "p.user_id={$uId} or p.echo_user_id={$uId}")
            );
            break;

        case 'private':
            $orm->where(array(
                'p.is_private=1',
                'OR' => array(
                    "p.id in (select post_id from {$postUser} where user_id={$uId})",
                    "p.user_id={$uId}",
                ),
            ));
            break;

        case 'starred':
            $orm->where(array(
                "p.id in (select post_id from {$postFeedback} where user_id={$uId} and star=1)",
            ));
            break;
        }

        return $orm;
    }

    public function userTimelineOrm($pubUserId=null)
    {
        $postUser = DotMesh_Model_PostUser::table();

        if (!$pubUserId && $this->orm) $pubUserId = $this->id;
        $uId = (int)DotMesh_Model_User::sessionUserId();
        $orm = DotMesh_Model_Post::i()->timelineOrm();

        $orm->where(array('AND'=>array(
            "p.user_id={$pubUserId} or p.echo_user_id={$pubUserId}",
            "p.is_private=0".($uId ? " or p.user_id={$uId} or p.id in (select post_id from {$postUser} where user_id={$uId})" : ''),
        )));

        return $orm;
    }

    public static function parseUri($uri)
    {
        $uri = trim($uri, '/');
        if (strpos($uri, '/')===false) { // local user
            return array(null, $uri);
        }
        $uri = str_replace('/u/', '/', $uri);
        $re = '`([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/([a-zA-Z0-9_-]+)`';
        if (!preg_match($re, $uri, $m)) {
            return false;
        }
        return array($m[1].$m[2], $m[3]);
    }

    public static function load($id, $field=null, $cache=false)
    {
        $model = parent::load($id, $field, $cache);
        if ($model) {
            $model->cacheStore('node_id,username');
        }
        return $model;
    }

    public static function find($uri, $create=false)
    {
        list($nodeUri, $username) = static::parseUri($uri);
        $nodeHlp = DotMesh_Model_Node::i();
        if (is_array($create) && !empty($create['node_id'])) {
            $nodeId = $create['node_id'];
        } else {
            $node = $nodeUri ? $nodeHlp->find($nodeUri, $create) : $nodeHlp->localNode();
            $nodeId = $node->id;
        }
        //$node->is_blocked?
        $user = static::load(array('node_id'=>$nodeId, 'username'=>$username));
        if (!$user && $create) {
            $create = (array)$create;
            $create['node_id'] = $nodeId;
            $create['username'] = $username;
            unset($create['id'], $create['secret_key'], $create['is_admin'], $create['is_confirmed'], $create['is_blocked']);
            $user = static::create($create)->save();
        }
        return $user;
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

    public function fullname()
    {
        return $this->firstname.' '.$this->lastname;
    }

    public function uri($full=false)
    {
        return $this->node()->uri('u', $full).$this->username;
    }

    public function thumbUri($size=100)
    {
        if (!$this->node()->is_local) {
            return $this->uri(true).'/thumb.jpg';
        }
        switch ($this->thumb_provider) {
        case 'link':
            return $this->thumb_uri;
        case 'file': //TODO: implement resize on server
            $rootPath = BConfig::i()->get('modules/DotMesh/thumb_root_path');
            return BApp::href($rootPath).'/'.$this->thumb_filename;
        case 'gravatar':
            return 'http://www.gravatar.com/avatar/'.md5($this->email).'?s='.$size.'&d=identicon';
        }
    }

    public function saveThumb($tmpFile, $filename)
    {
        $rootPath = BConfig::i()->get('modules/DotMesh/thumb_root_path');
        $prefix = substr($this->username, 0, 2);
        $dir = BRequest::i()->docRoot().'/'.$rootPath.'/'.$prefix;
        BUtil::ensureDir($dir);
        move_uploaded_file($tmpFile, $dir.'/'.$this->username.'-'.$filename);
        $this->set('thumb_filename', $prefix.'/'.$this->username.'-'.$filename);
        return $this;
    }

    public function feedUri($folder=null)
    {
        $params = array(
            'u' => $this->username,
            'h' => hash('sha256', $this->password_hash),
        );
        if (($s = BRequest::i()->get('s'))) {
            $params['s'] = $s;
        }
        return BUtil::setUrlQuery(BApp::href('a/'.$folder).'/feed.rss', $params);
    }

    /**
    * Generate signature for remote user identification
    *
    * Use $agentIP==null for server2server communication
    * Provide browser IP for double hash
    *
    * @param DotMesh_Model_Node $node
    * @param string $agentIP
    * @return string
    */
    public function generateRemoteSignature($node, $agentIP=null)
    {
        $localNode = DotMesh_Model_Node::i()->localNode();
        $signature = BUtil::sha512base64($localNode->secret_key.'|'.$node->secret_key.'|'.$this->secret_key);
        if ($agentIP) {
            if (true===$agentIP) {
                $agentIP = BRequest::i()->ip();
            }
            $signature = BUtil::sha512base64($agentIP.'|'.$signature);
        }
        return $signature;
    }

    public function validateRemoteSignature($node, $signature, $agentIP=null)
    {
        return $this->generateRemoteSignature($node, $agentIP)===$signature;
    }

    public function retrieveRemoteSignature()
    {
        $result = $this->node()->apiClient(array('ask_users' => array($this->username)));
        if (empty($result['ask_users'][$this->username]['remote_signature'])) {
            throw new BException('Could not retrieve remote signature');
        }
        $this->set('remote_signature', $result['ask_users'][$this->username]['remote_signature'])->save();
        return $this;
    }

    public function verifyRemoteSignature($requestSignature, $agentIP=null)
    {
        $retrieved = false;
        if (!$this->remote_signature) {
            $this->retrieveRemoteSignature();
            $retrieved = true;
        }
        $remoteSignature = $this->remote_signature;
        if (true===$agentIP) {
            $agentIP = BRequest::i()->ip();
        }
        if ($agentIP) {
            $remoteSignature = hash('sha512', $agentIP.'|'.$remoteSignature);
        }
        if ($remoteSignature === $requestSignature) {
            return true;
        } elseif ($retrieved) {
            return false;
        } else { // attempt to revalidate
            $this->retrieveRemoteSignature();
            $remoteSignature = $this->remote_signature;
            if ($agentIP) {
                $remoteSignature = hash('sha512', $agentIP.'|'.$remoteSignature);
            }
            return $remoteSignature === $requestSignature;
        }
    }

    public function acceptGuest($username, $signature)
    {

    }

    public function updateFromPost($r)
    {
        $this->set(BUtil::maskFields((array)$r, 'username,firstname,lastname,email,preferences,thumb_provider,thumb_uri,short_bio'));
        if (!empty($r['password'])) {
            $this->setPassword($r['password']);
        }
        if (!empty($_FILES['thumb']['tmp_name'])) {
            $this->saveThumb($_FILES['thumb']['tmp_name'], $_FILES['thumb']['name']);
        }
        $this->save();
    }

    public function subscribers($limit=20)
    {
        $orm = static::orm('u')
            ->join('DotMesh_Model_UserSub', array('us.sub_user_id','=','u.id'), 'us')
            ->where('us.pub_user_id', $this->id)
            ->limit($limit);
        if (($p = BRequest::i()->get('p'))) {
            $orm->offset($limit*($p-1));
        }
        return $orm->find_many();
    }

    public function subscribedToUsers($limit=20)
    {
        $orm = static::orm('u')
            ->join('DotMesh_Model_UserSub', array('us.pub_user_id','=','u.id'), 'us')
            ->where('us.sub_user_id', $this->id)
            ->limit($limit);
        if (($p = BRequest::i()->get('p'))) {
            $orm->offset($limit*($p-1));
        }
        return $orm->find_many();
    }

    public function subscribedToTags($limit=20)
    {
        $orm = DotMesh_Model_Tag::i()->orm('t')
            ->join('DotMesh_Model_TagSub', array('ts.pub_tag_id','=','t.id'), 'ts')
            ->where('ts.sub_user_id', $this->id)
            ->limit($limit);
        if (($p = BRequest::i()->get('p'))) {
            $orm->offset($limit*($p-1));
        }
        return $orm->find_many();
    }

    public function subscribersCnt()
    {
        $cnt = DotMesh_Model_UserSub::i()->orm()
            ->where('pub_user_id', $this->id)->select('(count(*))', 'value')->find_one();
        return $cnt ? $cnt->value : 0;
    }

    public function subscribedToUsersCnt()
    {
        $cnt = DotMesh_Model_UserSub::i()->orm()
            ->where('sub_user_id', $this->id)->select('(count(*))', 'value')->find_one();
        return $cnt ? $cnt->value : 0;
    }

    public function postsCnt()
    {
        $cnt = DotMesh_Model_Post::i()->orm()
            ->where('user_id', $this->id)->where('is_private', 0)->select('(count(*))', 'value')->find_one();
        return $cnt ? $cnt->value : 0;
    }

    public function isSubscribedToUser($user)
    {
        $userId = is_numeric($user) ? $user : $user->id;
        $sub = DotMesh_Model_UserSub::i()->load(array('pub_user_id'=>$userId, 'sub_user_id'=>$this->id));
        return $sub ? true : false;
    }

    public function isSubscribedToTag($tag)
    {
        $tagId = is_numeric($tag) ? $tag : $tag->id;
        $sub = DotMesh_Model_TagSub::i()->load(array('pub_tag_id'=>$tagId, 'sub_user_id'=>$this->id));
        return $sub ? true : false;
    }

    public function subscribeToUser($user, $updateTo=true)
    {
        if (is_string($user)) {
            $user = DotMesh_Model_User::i()->find($user, true);
        }
        if (!$user) {
            throw new BException('Invalid user');
        }
        if (is_object($user)) {
            $userId = $user->id;
        } elseif (is_numeric($user)) {
            $userId = $user;
        } else {
            throw new BException('Invalid user');
        }
        if ($userId===$this->id) {
            throw new BException('Can not subscribe to yourself');
        }
        $hlp = DotMesh_Model_UserSub::i();
        $where = array('pub_user_id'=>$userId, 'sub_user_id'=>$this->id);
        $curSub = $hlp->load($where);
        if (!$user->node()->is_local && ($updateTo && !$curSub || !$updateTo && $curSub)) {
            $sessUser = DotMesh_Model_User::i()->sessionUser();
            $request = array(
                'users' => array($sessUser),
                'subscriptions' => array(
                    array('type'=>'user', 'sub'=>$sessUser->uri(), 'pub'=>$user->uri(), 'subscribe'=>$updateTo),
                ),
            );
            if (!$user->remote_signature) {
                $request['ask_users'][] = $user->username;
            }
            $user->node()->apiClient($request);
        }
        if ($updateTo && !$curSub) {
            $hlp->create($where)->save();
        } elseif (!$updateTo && $curSub) {
            $hlp->delete_many($where);
        }

        return $this;
    }

    public function subscribeToTag($tag, $updateTo=true)
    {
        if (is_string($tag)) {
            $tag = DotMesh_Model_Tag::i()->find($tag);
        }
        if (!$tag) {
            throw new BException('Invalid tag');
        }
        if (is_object($tag)) {
            $tagId = $tag->id;
        } elseif (is_numeric($tag)) {
            $tagId = $tag;
        } else {
            throw new BException('Invalid tag');
        }
        $hlp = DotMesh_Model_TagSub::i();
        $where = array('pub_tag_id'=>$tagId, 'sub_user_id'=>$this->id);
        $curSub = $hlp->load($where);
        if ($updateTo && !$curSub) {
            $hlp->create($where)->save();
        } elseif (!$updateTo && $curSub) {
            $hlp->delete_many($where);
        }
        return $this;
    }

    public static function trendingUsers()
    {
        return array();
    }
}
