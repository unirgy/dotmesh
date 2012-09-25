<?php

class DotMesh_Model_Node extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_node';

    protected static $_localNode;
    
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
            static::$_localNode = static::load(1);
        }
        return static::$_localNode;
    }

    public static function setup($form)
    {
        try {
            if (empty($form['node_uri'])
                || empty($form['username']) || empty($form['email']) || empty($form['name'])
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
                'is_modrewrite' => $form['is_modrewrite'],
                'private_key' => BUtil::randomString(60),
            ))->save();
            $user = DotMesh_Model_User::i()->create(array(
                'node_id' => $node->id,
                'username' => $form['username'],
                'name' => $form['name'],
                'email' => $form['email'],
                'private_key' => BUtil::randomString(60),
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
    
    public static function find($uri, $create=false, $fetch=false) 
    {
        if (strpos($uri, '?')!==false || strpos($uri, '#')!==false) {
            throw new BException('Invalid node URI');
        }
        $node = static::load($uri, 'uri');
        if (!$node && $create) {
            $node = static::create(array('uri'=>$uri));
            if ($fetch) {
                $node->fetchInfo();
            }
            $node->save();
        }
        return $node;
    }
    
    public function fetchInfo() 
    {
        $result = BUtil::remoteHttp('POST', $this->uri.'/n/');
        $info = BUtil::fromJson($result);
        $this->is_https = $info['is_https'];
        $this->is_modrewrite = $info['is_modrewrite'];
        return $this;
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
}
