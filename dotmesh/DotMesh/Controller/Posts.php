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

class DotMesh_Controller_Posts extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        $layout = BLayout::i();
        $hlp = DotMesh_Model_Post::i();
        $postname = $r->param('postname');
        $post = DotMesh_Model_Node::i()->localNode()->post($postname);
        if (!$post) {
            $this->forward(true);
            return;
        }

        BLayout::i()->view('head')->canonical($post->uri(true));

        $timeline = $hlp->fetchTimeline($post->threadTimelineOrm());
        $layout->view('timeline')->set('timeline', $timeline);

        if ($r->xhr()) {
            $layout->applyLayout('xhr-timeline');
        } else {
            $layout->applyLayout('/thread');
            $sessUserId = DotMesh_Model_User::i()->sessionUserId();
            $mentions = array('+'.$post->user()->uri());
            foreach ($post->mentionedUsers() as $pUser) {
                if ($pUser->id!=$sessUserId) {
                    $mentions[] = '+'.$pUser->uri();
                }
            }
            $layout->view('thread')->set('post', $post);
            $layout->view('compose')->set('post', $post)->set('contents', join(' ', $mentions).' ');
            $layout->view('timeline')->set(array(
                'title' => BLocale::i()->_("Thread timeline"),
                'feed_uri' => $post->uri(true).'/feed.rss',
            ));
        }
    }

    public function action_index__POST()
    {
        try {
            $r = BRequest::i();
            $redirectUrl = BApp::href();
            if (!DotMesh_Model_User::isLoggedIn()) {
                throw new BException('Not logged in');
            }
            $hlp = DotMesh_Model_Post::i();
            $postname = $r->param('postname');
            if ($postname) {
                $post = $hlp->load($postname, 'postname');
                if (!$post) {
                    throw new BException('Invalid post identifier');
                }
            }
            $do = $r->post('do');
            switch ($do) {
            case 'new':
                if (!empty($post)) {
                    throw new BException('Invalid post action');
                }
                $data = $r->post();
                $post = $hlp->submitNewPost($data);
                $result = $post->result;
                if ($post->thread_id) {
                    $redirectUrl = $post->uri(true);
                }
                $result['message'] = 'Your post has been submited';
                break;

            case 'delete':
                $sessionUser = DotMesh_Model_User::sessionUser();
                if ($sessionUser->id!==$post->user_id && !$sessionUser->is_admin) {
                    throw new BException('Post does not belong to the logged in user');
                }
                $post->delete();
                break;
            }
            foreach (explode(',','echo,star,report,vote_up,vote_down') as $f) {
                if (($fb = $r->post($f))) {
                    $post->submitFeedback($f, $fb);
                }
            }
            $result['status'] = 'success';
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

    public function action_api1_json__POST()
    {
        try {
            if (!DotMesh_Model_User::isLoggedIn()) {
                throw new BException('Not logged in');
            }
            $request = BRequest::i()->json();
            if (!$request) {
                $request = BRequest::i()->post();
            }
            if (empty($request['type'])) {
                throw new BException('Invalid request type');
            }
            $postname = BRequest::i()->param('postname');
            $post = DotMesh_Model_Post::i()->find($postname);
            if (!$post) {
                throw new BException('Invalid post');
            }
            switch ($request['type']) {
            case 'feedback':
                $post->submitFeedback($request['field'], $request['value']);
                $result = array('status'=>'success', 'message'=>'Feedback submitted');
                $orm = DotMesh_Model_PostFeedback::i()->orm()->where('post_id', $post->id);
                foreach (explode(',','echo,star,flag,vote_up,vote_down') as $f) {
                    $orm->select('(sum('.$f.'))', $f);
                    $result['value'][$f] = (int)$post->feedback->$f;
                }
                $total = $orm->find_one();
                foreach ($total->as_array() as $k=>$v) {
                    $result['total'][$k] = $v ? $v : '';
                }
                break;
            }
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }

        BResponse::i()->json($result);
    }

    public function action_feed_rss()
    {

    }
}

