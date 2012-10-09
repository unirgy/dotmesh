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
            $mentions = array();
            if ($post->user()->id!==$sessUserId) {
                $username = $post->node()->is_local ? $post->user()->username : $post->user()->uri();
                $mentions['+'.$username] = 1;
            }
            foreach ($post->mentionedUsers() as $pUser) {
                if ($pUser->id!=$sessUserId) {
                    $username = $pUser->node()->is_local ? $pUser->username : $pUser->uri();
                    $mentions['+'.$username] = 1;
                }
            }
            $layout->view('thread')->set('post', $post);
            $layout->view('compose')->set('post', $post)->set('contents', join(' ', array_keys($mentions)).' ');
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
                if ('_'===$postname) {
                    $post = $hlp->find($r->post('post_uri'));
                } else {
                    $post = $hlp->load($postname, 'postname');
                }
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

                if ($r->xhr()) {
                    $sessUser = DotMesh_Model_User::i()->sessionUser();
                    $view = BLayout::i()->view('timeline');
                    $view->set('timeline', array('rows'=>array($post)));
                    $result['timeline_html'] = (string)$view;
                    $result['user_posts_cnt'] = $sessUser->postsCnt();

                    $distData = $post->distribute();
                    if (!empty($distData['nodes'])) {
                        foreach ($distData['nodes'] as $rNode) {
                            $uri = $rNode->uri(null, true);
                            $result['remote_nodes'][] = array(
                                //'uri' => $uri,
                                'api_uri' => $uri.'/p/_/api1.json',
                                //'remote_signature' => $sessUser->generateRemoteSignature($rNode), // never send live
                                'remote_signature_ip' => $sessUser->generateRemoteSignature($rNode, true),
                            );
                        }
                        $result['post'] = BUtil::maskFields($post->as_array(), 'postname,is_private,is_tweeted,create_dt');
                        $result['post']['preview'] = $post->normalizePreviewUsersTags();
                        $result['post']['post_uri'] = $post->uri();
                        $result['post']['user_uri'] = $sessUser->uri();
                    }
                }

                $result['status'] = 'success';
                $result['message'] = 'Your post has been submited';
                break;

            case 'delete':
                $sessionUser = DotMesh_Model_User::sessionUser();
                if ($sessionUser->id!==$post->user_id && !$sessionUser->is_admin) {
                    throw new BException('Post does not belong to the logged in user');
                }
                $post->delete();
                $result['status'] = 'success';
                $result['message'] = 'Post has been deleted';
                break;

            default:
                throw new BException('Invalid form action');
            }
            //$post->submitFeedback($r->post());
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
            $r = BRequest::i();
            $request = $r->json();
            if (!$request) {
                $request = $r->post();
            }
            $postHlp = DotMesh_Model_Post::i();
            if (empty($request['type'])) {
                throw new BException('Invalid request type');
            }
            if (!empty($request['user_uri']) && !empty($request['post_uri']) && !empty($request['remote_signature_ip'])) {
                BResponse::i()->cors();
                $user = DotMesh_Model_User::i()->find($request['user_uri'], true);
                if (!$user) {
                    throw new BException('Invalid user uri');
                }
                if (!$user->verifyRemoteSignature($request['remote_signature_ip'], true)) {
                    throw new BException('Invalid remote signature');
                }
                $post = $postHlp->find($request['post_uri']);
                $remote = true;
            } elseif ($r->param('postname')) {
                $user = DotMesh_Model_User::i()->sessionUser();
                if (!$user) {
                    throw new BException('Not logged in');
                }
                $post = $postHlp->find($r->param('postname'));
                $remote = false;
            } else {
                throw new Exception('Invalid request');
            }
            switch ($request['type']) {
            case 'new':
                if (empty($request['post'])) {
                    throw new Exception('Missing post data');
                }
                $data = $request['post'];
                $data['node_id'] = $user->node()->id;
                $data['user_id'] = $user->id;
                $post = $postHlp->receiveRemotePost($data);
                $result = $post->result;
                if ($post->thread_id) {
                    $redirectUrl = $post->uri(true);
                }
                $result['message'] = 'Your post has been submited';
                break;

            case 'feedback':
                if (empty($post)) {
                    throw new BException('Invalid post');
                }
                if ($request['field']=='pin') {
                    $post->set('is_pinned', 0)->save();
                    $result = array('status'=>'success', 'message'=>'Post is unpinned');
                } else {
                    $post->submitFeedback(array($request['field']=>$request['value']), $user->id, $remote);
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
        $r = BRequest::i();
        $localNode = DotMesh_Model_Node::i()->localNode();
        $hlp = DotMesh_Model_Post::i();
        $localNode = DotMesh_Model_Node::i()->localNode();
        $post = $localNode->post($r->param('postname'));
        if (!$post) {
            BResponse::i()->status(404, 'Invalid post', true);
        }
        $orm = $post->threadTimelineOrm();
        $timeline = $hlp->fetchTimeline($orm);
        BResponse::i()->contentType('text/xml')->set($hlp->toRss(array(
            'title' => $post->uri().' :: Post Timeline :: '.$localNode->uri(),
            'link' => $post->uri(true),
        ), $timeline['rows']));
    }
}

