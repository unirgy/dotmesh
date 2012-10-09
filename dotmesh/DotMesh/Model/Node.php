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

class DotMesh_Model_Node extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_node';
    protected static $_cacheAuto = true;

    protected static $_localNode;

    protected static $_localConfig;

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
            $localNodeUri = BConfig::i()->get('modules/DotMesh/local_node_uri');
            if ($localNodeUri) {
                static::$_localNode = static::load($localNodeUri, 'uri');
            }
            if (!static::$_localNode) {
                $r = BRequest::i();
                $nodeName = trim($r->httpHost().$r->webRoot(), '/');

                static::$_localNode = static::load($nodeName, 'uri');
                if (static::$_localNode) {
                    return static::$_localNode;
                }

                $nodes = static::orm()->where('is_local', 1)->find_many();
                foreach ($nodes as $node) {
                    if (/*$node->uri===$nodeName || */preg_match('#'.preg_quote($node->uri).'$#', $nodeName)) {
                        static::$_localNode = $node;
                        break;
                    }
                }
            }
        }
        return static::$_localNode;
    }

    public function afterCreate()
    {
        parent::afterCreate();
        $this->set('api_version', 1);
        $this->set('secret_key', BUtil::randomString(64), null);
    }

    public static function setup($form)
    {
        try {
            if (empty($form['node_uri'])
                || empty($form['username']) || empty($form['email'])
                //|| empty($form['firstname']) || empty($form['lastname'])
                || empty($form['password']) || empty($form['password_confirm'])
                || $form['password'] !== $form['password_confirm']
                || !isset($form['is_https'])
            ) {
                throw new BException('Missing or invalid input');
            }
            $node = DotMesh_Model_Node::i()->create(array(
                'id' => 1,
                'uri' => $form['node_uri'],
                'support_email' => $form['support_email'],
                'is_local' => 1,
                'is_https' => $form['is_https'],
                'is_rewrite' => $form['is_rewrite'],
            ))->save();
            $user = DotMesh_Model_User::i()->create(array(
                'node_id' => $node->id,
                'username' => $form['username'],
                'firstname' => !empty($form['firstname']) ? $form['firstname'] : '',
                'lastname' => !empty($form['lastname']) ? $form['lastname'] : '',
                'email' => $form['email'],
                'secret_key' => BUtil::randomString(64),
                'is_admin' => 1,
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

    public static function load($id, $field=null, $cache=false)
    {
        $model = parent::load($id, $field, $cache);
        if ($model) {
            $model->cacheStore('uri');
        }
        return $model;
    }

    public static function find($uri, $create=false)
    {
        if (strpos($uri, '?')!==false || strpos($uri, '#')!==false) {
            throw new BException('Invalid node URI');
        }
        $node = static::load($uri, 'uri');
        if (!$node && $create) {
            $create = (array)$create;
            unset($create['id'], $create['secret_key'], $create['score'], $create['is_blocked']);
            $create['uri'] = $uri;
            $node = static::create($create)->save();
        }
        return $node;
    }

    public function apiClient($request)
    {
        $localNode = static::localNode();
        $userHlp = DotMesh_Model_User::i();

        $request['node'] = BUtil::maskFields($localNode->as_array(), 'uri,api_version,is_https,is_rewrite');

        if (!empty($request['users'])) {
            $usersData = array();
            foreach ($request['users'] as $user) {
                if (is_numeric($user)) {
                    $user = DotMesh_Model_User::i()->load($user);
                } elseif (is_string($user)) {
                    $user = $userHlp->find($user);
                }
                $userData = BUtil::maskFields($user->as_array(), 'username,firstname,lastname');
                //TODO: implement sharing of remote users (security considerations?)
                #if ($user->node()->is_local) {
                    $userData['remote_signature'] = $user->generateRemoteSignature($this);
                #} else {
                #    $userData['node_uri'] = $user->node()->uri();
                #}
                $usersData[] = $userData;
            }
            $request['users'] = $usersData;
        }

        if (!empty($request['posts'])) {
            $postsData = array();
            foreach ($request['posts'] as $post) {
                if (is_numeric($post)) {
                    $post = DotMesh_Model_Post::i()->load($post);
                } elseif (is_string($post)) {
                    $post = $localNode->post($post);
                }
                $postData = BUtil::maskFields($post->as_array(), 'postname,is_private,is_tweeted,create_dt');
                $postData['user_uri'] = $post->user()->uri();
                if ($post->echo_user_id) {
                    $postData['echo_user_uri'] = $post->echoUser()->uri();
                }
                $postData['preview'] = $post->normalizePreviewUsersTags();
                $postsData[] = $postData;
            }
            $request['posts'] = $postsData;
        }

        if (!empty($request['feedbacks'])) {
            $feedbacksData = array();
            foreach ($request['feedbacks'] as $fb) {
                if (is_numeric($fb['user'])) {
                    $fb['user'] = DotMesh_Model_User::i()->load($fb['user']);
                }
                if (is_numeric($fb['post'])) {
                    $fb['post'] = DotMesh_Model_Post::i()->load($fb['post']);
                }
                $fbData = BUtil::maskFields($fb, 'echo,star,report,vote_up,vote_down');
                $fbData['post_uri'] = $fb['post']->uri();
                $fbData['user_uri'] = $fb['user']->uri();
                $feedbacksData[] = $fbData;
            }
            $request['feedbacks'] = $feedbacksData;
        }

        $response = BUtil::remoteHttp('POST', $this->uri(null, true).'/n/api1.json', BUtil::toJson($request));
        $result = BUtil::fromJson($response[0]);
        if (!empty($result['node'])) {
            $this->set(BUtil::maskFields($result['node'], 'api_version,is_https,is_rewrite'))->save();
        }

        //TODO: improve performance by using user objects from request
        if (!empty($request['ask_users'])) {
            foreach ($request['ask_users'] as $username) {
                if (empty($result['ask_users'][$username])) {
                    continue; //TODO: how to handle?
                }
                $userData = $result['ask_users'][$username];
                if (empty($userData['remote_signature'])) {
                    continue; //TODO: how to handle?
                }
                $userData = BUtil::maskFields($userData, 'firstname,lastname,remote_signature');
                $userData['node_id'] = $this->id;
                $userData['username'] = $username;
                $user = $userHlp->find($username, $userData);
                if (!$user->remote_signature) {
                    $user->set('remote_signature', $userData['remote_signature'])->save();
                }
            }
        }
        return $this;
    }

    public static function apiServer($request)
    {
        if (empty($request['node']['uri'])) {
            throw new BException('Invalid node data');
        }

        $uriArr = explode('/', $request['node']['uri'], 2);
        $remoteHost = $uriArr[0];
        if (!BRequest::i()->verifyOriginHostIp('HOST', $remoteHost)) {
            throw new BException('Unauthorized node origin IP');
        }

        $remoteNode = static::find($request['node']['uri'], $request['node']);
        if ($remoteNode->is_blocked) {
            throw new BException('Node is blocked');
        }

        if (!empty($requst['node']['is_compromised'])) {
            DotMesh_Model_User::i()->update_many(array('remote_signature'=>null), array('node_id'=>$remoteNode->id));
        }

        $localNode = DotMesh_Model_Node::i()->localNode();
        $result = array(
            'node' => BUtil::maskFields($localNode->as_array(), 'uri,api_version,is_https,is_rewrite'),
        );

        $userHlp = DotMesh_Model_User::i();
        $postHlp = DotMesh_Model_Post::i();

        if (!empty($request['users'])) {
            foreach ($request['users'] as $userData) {
                if (empty($userData['username'])) {
                    $result['users'][] = array('status'=>'error', 'message'=>'Invalid user data');
                    continue;
                }
                $userData['node_id'] = $remoteNode->id;
                $user = $userHlp->find($userData['username'], $userData);
                $result['users'][] = array('username'=>$userData['username'], 'status'=>'success');
            }
        }

        if (!empty($request['ask_users'])) {
            foreach ($request['ask_users'] as $username) {
                $user = $localNode->user($username);
                if (!$user) {
                    $result['ask_users'][$username] = array('status'=>'error', 'message'=>'Not found');
                    continue;
                }
                $result['ask_users'][$username] = array(
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'remote_signature' => $user->generateRemoteSignature($remoteNode),
                );
            }
        }

        if (!empty($request['subscriptions'])) {
            foreach ($request['subscriptions'] as $s) {
                try {
                    if (empty($s['type']) || empty($s['sub']) || empty($s['pub'])) {
                        throw new BException('Incomplete request');
                    }
                    $subUser = $userHlp->find($s['sub']);
                    if (!$subUser) {
                        throw new BException('Invalid subscriber user');
                        continue;
                    }
                    $updateTo = isset($s['subscribe']) ? (int)$s['subscribe'] : true;
                    switch ($s['type']) {
                    case 'user':
                        $pubUser = $userHlp->find($s['pub']);
                        if (!$pubUser || $pubUser->node_id!=$localNode->id) {
                            throw new BException('Invalid publisher user');
                        }
                        $subUser->subscribeToUser($pubUser, $updateTo);
                        $s['status'] = 'success';
                        break;

                    case 'tag':
                        $pubTag = DotMesh_Model_Tag::i()->find($s['pub']);
                        if (!$pubTag || $pubTag->node_id!=$localNode->id) {
                            throw new BException('Invalid publisher tag');
                        }
                        $subUser->subscribeToTag($pubTag, $updateTo);
                        $s['status'] = 'success';
                        break;

                    default:
                        throw new BException('Invalid subscription type');
                    }
                } catch (Exception $e) {
                    $s['status'] = 'error';
                    $s['message'] = 'Incomplete request';
                }
                $result['subscriptions'][] = $s;
            }
        }

        if (!empty($request['posts'])) {
            foreach ($request['posts'] as $p) {
                try {
                    if (empty($p['postname']) || empty($p['user_uri']) || empty($p['preview']) || empty($p['create_dt'])) {
                        throw new BException('Incomplete request');
                    }
                    $user = $userHlp->find($p['user_uri'], true);
                    $data['node_id'] = $remoteNode->id;
                    $data['user_id'] = $user->id;
                    if (!empty($p['echo_user_uri'])) {
                        $echoUser = $userHlp->find($p['echo_user_uri'], true);
                        $data['echo_user_id'] = $echoUser->id;
                    }
                    $post = $postHlp->receiveRemotePost($data);
                    $p['status'] = 'success';
                } catch (Exception $e) {
                    $p['status'] = 'error';
                    $p['message'] = 'Incomplete request';
                }
                $result['posts'][] = $p;
            }
        }
        if (!empty($request['feedbacks'])) {
            foreach ($request['feedbacks'] as $fb) {
                try {
                    if (empty($p['post_uri']) || empty($p['user_uri'])) {
                        throw new BException('Incomplete request');
                    }
                    $post = $postHlp->find($p['post_uri']);
                    if (!$post || $post->node_id!==$localNode->id) {
                        throw new BException('Invalid post');
                    }
                    $user = $userHlp->find($p['user_uri'], true);
                    if ($user->node_id!==$remoteNode->id) {
                        throw new BException('Invalid user');
                    }
                    $post->receiveRemoteFeedback($data);
                    $p['status'] = 'success';
                } catch (Exception $e) {
                    $fb['status'] = 'error';
                    $fb['message'] = 'Incomplete request';
                }
                $result['feedbacks'][] = $fb;
            }
        }

        return $result;
    }

    public function uri($type=null, $full=false)
    {
        $uri = '';
        if ($full) {
            $uri .= $this->is_https ? 'https://' : 'http://';
        }
        $uri .= trim($this->uri,'/');
        if ($full && !$this->is_rewrite) {
            $uri .= '/dotmesh.php';
        }
        if ($type) {
            $uri .= '/'.$type.'/';
        }
        return $uri;
    }

    public function user($username, $field='username')
    {
        return DotMesh_Model_User::i()->load(array('node_id'=>$this->id, $field=>$username));
    }

    public function tag($tagname)
    {
        return DotMesh_Model_Tag::i()->load(array('node_id'=>$this->id, 'tagname'=>$tagname));
    }

    public function post($postname)
    {
        return DotMesh_Model_Post::i()->load(array('node_id'=>$this->id, 'postname'=>$postname));
    }

    public static function config($key=null)
    {
        if (!static::$_localConfig) {
            static::$_localConfig = BConfig::i()->get('modules/DotMesh');
        }
        return $key ? (!empty(static::$_localConfig[$key]) ? static::$_localConfig[$key] : null) : static::$_localConfig;
    }
}
