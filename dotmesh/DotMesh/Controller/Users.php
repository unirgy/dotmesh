<?php

class DotMesh_Controller_Users extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        $layout = BLayout::i();
        $username = $r->param('username');
        $user = DotMesh_Model_Node::i()->localNode()->user($username);
        if (!$user) {
            $this->forward(true);
            return;
        }
        $timeline = DotMesh_Model_Post::i()->fetchTimeline($user->userTimelineOrm());
        $layout->view('timeline')->set('timeline', $timeline);

        if ($r->xhr()) {
            $layout->applyLayout('xhr-timeline');
        } else {
            $layout->applyLayout('/user');
            $layout->view('head')->addTitle($user->username);
            $layout->view('user')->set('user', $user);
            $layout->view('timeline')->set(array(
                'title' => BLocale::i()->_("%s's timeline", $user->fullname()),
                'feed_uri' => $user->uri(true).'/feed.rss',
            ));
        }
    }

    public function action_index__POST()
    {
        try {
            $r = BRequest::i();
            $redirectUrl = $r->currentUrl();
            $sessUser = DotMesh_Model_User::i()->sessionUser();
            if (!$sessUser) {
                throw new BException('Not logged in');
            }
            $subscribe = $r->post('subscribe');
            if (!is_null($subscribe)) {
                $sessUser->subscribeToUser($r->post('user_uri'), (int)$subscribe);
                $message = ((int)$subscribe ? 'Subscribed to' : 'Unsubscribed from').' +%s';
                $result = array('status'=>'success', 'message'=>BLocale::i()->_($message, $r->post('user_uri')));
            }
        } catch (BException $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        if ($r->xhr()) {
            BResponse::i()->json($result);
        } else {
            BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
        }
    }

    public function action_api1_json()
    {

    }

    public function action_feed_rss()
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