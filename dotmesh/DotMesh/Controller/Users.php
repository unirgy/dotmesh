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

class DotMesh_Controller_Users extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        $layout = BLayout::i();
        $username = $r->param('username');
        if (!$username) {
            BResponse::i()->redirect(BApp::href().'?'.BRequest::i()->rawGet());
        }
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
            $layout->view('head')->addTitle($user->username)->canonical($user->uri(true));
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
        $r = BRequest::i();
        $localNode = DotMesh_Model_Node::i()->localNode();
        $hlp = DotMesh_Model_Post::i();
        $localNode = DotMesh_Model_Node::i()->localNode();
        $user = $localNode->user($r->param('username'));
        if (!$user) {
            BResponse::i()->status(404, 'Invalid user', true);
        }
        $orm = $user->userTimelineOrm();
        $timeline = $hlp->fetchTimeline($orm);
        BResponse::i()->contentType('text/xml')->set($hlp->toRss(array(
            'title' => $user->username.' :: User Timeline :: '.$localNode->uri(),
            'link' => $user->uri(true),
        ), $timeline['rows']));
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