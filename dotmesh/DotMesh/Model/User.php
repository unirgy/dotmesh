<?php
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
    
    public static function signup($r)
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
            $r = BUtil::maskFields($r, 'username,email,password,secret_key,firstname,lastname,thumb_provider');
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
        return true;
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
    
    public function userTimelineOrm($pubUserId=null)
    {
        $postUser = DotMesh_Model_PostUser::table();
        
        if (!$pubUserId && $this->orm) $pubUserId = $this->id;
        $uId = (int)DotMesh_Model_User::sessionUserId();
        $orm = DotMesh_Model_Post::i()->timelineOrm();
        
        $orm->where(array('AND'=>array(
            "p.user_id={$pubUserId} or p.echo_user_id={$pubUserId}",
            "p.is_private=0".($uId ? " or p.id in (select post_id from {$postUser} where user_id={$uId})" : ''),
        )));
        
        return $orm;
    }
        
    public static function parseUri($uri) 
    {
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
        if ($model) $model->cacheStore('node_id,username');
        return $model;
    }

    public static function find($uri, $create=false) 
    {
        list($nodeUri, $username) = static::parseUri($uri);
        $nodeHlp = DotMesh_Model_Node::i();
        $node = $nodeUri ? $nodeHlp->find($nodeUri, $create) : $nodeHlp->localNode();
        //$node->is_blocked?
        $user = static::load(array('node_id'=>$node->id, 'username'=>$username));
        if (!$user && $create) {
            $create = (array)$create;
            $create['node_id'] = $node->id;
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
        switch ($this->thumb_provider) {
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
    
    public function generateRemoteSignature($node)
    {
        return base64_encode(pack('H*', hash('sha512', $node->secret_key.'|'.$this->secret_key)));
    }
    
    public function validateRemoteSignature($node, $signature)
    {
        return $this->generateRemoteSignature($node)===$signature;
    }
    
    public function confirmRemoteSignature($signature)
    {
        $result = BUtil::fromJson(BUtil::remoteHttp('POST', $this->uri().'.json', array(
            'do' => 'confirm_signature',
            'signature' => $signature,
            'node_info' => $this->node()->info(),
        )));
        if ($result && $result['status']=='success') {
            $this->remote_signature = $signature;
        }
        return $this;
    }

    public function acceptGuest($username, $signature)
    {
        
    }
    
    public function updateFromPost($r)
    {
        $this->set(BUtil::maskFields((array)$r, 'username,firstname,lastname,email'));
        if (!empty($r['password'])) {
            $this->setPassword($r['password']);   
        }
        if (!empty($_FILES['thumb']['tmp_name'])) {
            $this->saveThumb($_FILES['thumb']['tmp_name'], $_FILES['thumb']['name']);
        }
        $this->save();
    }
}
