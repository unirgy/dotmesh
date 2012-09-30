<?php

class DotMesh_Controller_Users extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $username = BRequest::i()->param('username');
        $user = DotMesh_Model_Node::i()->localNode()->user($username);
        $timeline = DotMesh_Model_Post::i()->fetchTimeline($user->userTimelineOrm());
        BLayout::i()->applyLayout('/user');
        BLayout::i()->view('user')->set('user', $user);
        BLayout::i()->view('timeline')->set('timeline', $timeline);
    }
    
    public function action_json()
    {

    }

    public function action_rss()
    {

    }
    
    public function action_thumb()
    {    
        $username = BRequest::i()->param(1);
        
        // try to save some time by finding local file
        $rootPath = BConfig::i()->get('modules/DotMesh/thumbs_root_path');
        $pattern = DOTMESH_ROOT_DIR.'/'.$rootPath.'/'.substr($username, 0, 2).'/'.$username;
        foreach (glob($pattern, GLOB_NOSORT) as $filename) {
            BResponse::i()->sendFile($filename);
        }
        
        $user = DotMesh_Model_Node::i()->localNode()->user($username);
        if ($user) {
            switch ($user->thumb_provider) {
            case 'file':    
                $filename = DOTMESH_ROOT_DIR.'/'.$rootPath.'/'.$user->thumb_filename;
                BResponse::i()->sendFile($filename);
            case 'gravatar':
                BResponse::i()->redirect($user->thumbUri());
            }
        }
    }
}