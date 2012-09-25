<?php
class DotMesh_Model_User extends BModelUser
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'dm_user';
    
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
        $user = parent::signup($r);
        $user->set('private_key', BUtil::randomString(32))->save();
        return $user;
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
    
    public function userTimelineOrm()
    {
        $postUser = DotMesh_Model_PostUser::table();
        
        $pubUserId = $this->id;
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
        $uri = str_replace('/u/', '/', $uri);
        $re = '|([a-zA-Z0-9][a-z0-9.-]+\.[a-zA-Z]{2,6})(\S*)/([a-zA-Z0-9_-]+)|';
        if (!preg_match($re, $uri, $m)) {
            return false;
        }
        return array($m[1].$m[2], $m[3]);
    }
    
    public static function find($uri, $create=false) 
    {
        list($nodeUri, $username) = static::parseUri($uri);
        $node = DotMesh_Model_Node::i()->find($nodeUri, $create);
        //$node->is_blocked?
        $data = array('node_id'=>$node->id, 'username'=>$username);
        $user = static::load($data);
        if (!$user && $create) {
            $user = static::create($data)->save();
        }
        return $user;
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
        return $this->node()->uri('u', $full).$this->username;
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
}
