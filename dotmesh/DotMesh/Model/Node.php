<?php

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
        $request['node'] = BUtil::maskFields(static::localNode()->as_array(), 'uri,api_version,is_https,is_rewrite');
        $response = BUtil::remoteHttp('POST', $this->uri(null, true).'/n/api1.json', BUtil::toJson($request));
        $result = BUtil::fromJson($response[0]);
var_dump($result);
        if (!empty($result['node'])) {
            $this->set(BUtil::maskFields($result['node'], 'api_version,is_https,is_rewrite'))->save();
        }

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
                $userData['username'] = $username;
                $user = DotMesh_Model_User::i()->find($this->uri().'/'.$username, $userData);
                //$user->set('remote_signature', $userData['remote_signature'])->save();
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
        $localNode = DotMesh_Model_Node::i()->localNode();
        $result = array(
            'node' => BUtil::maskFields($localNode->as_array(), 'uri,api_version,is_https,is_rewrite'),
        );
        if (!empty($request['users'])) {
            $hlp = DotMesh_Model_User::i();
            foreach ($request['users'] as $userData) {
                if (empty($userData['username'])) {
                    $result['users'][] = array('status'=>'error', 'message'=>'Invalid user data');
                    continue;
                }
                $userData['node_id'] = $remoteNode->id;
                $user = $hlp->find($userData['username'], $userData);
                $result['users'][] = array('username'=>$userData['username'], 'status'=>'success');
            }
        }

        if (!empty($request['ask_users'])) {
            $localNode = static::localNode();
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
        return $result;
    }

    public function uri($type=null, $full=false)
    {
        $uri = '';
        if ($full) {
            $uri .= $this->is_https ? 'https://' : 'http://';
        }
        $uri .= trim($this->uri,'/');
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
