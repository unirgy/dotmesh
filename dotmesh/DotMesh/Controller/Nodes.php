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

class DotMesh_Controller_Nodes extends DotMesh_Controler_Abstract
{
    public function action_404()
    {
        BLayout::i()->applyLayout('404');
    }

    public function action_503()
    {
        BLayout::I()->applyLayout('503');
    }

    public function action_setup()
    {
        if (DotMesh_Model_Node::i()->localNode()) {
            BResponse::i()->redirect(BApp::href());
        }
        $r = BRequest::i();
        BLayout::i()->view('setup')->set(array(
            'node_uri' => trim($r->httpHost().$r->webRoot(), '/'),
            'is_https' => $r->https(),
            'is_rewrite' => $r->modRewriteEnabled(),
        ));
        BLayout::i()->applyLayout('/setup');
    }

    public function action_setup__POST()
    {
        $redirectUrl = BApp::href();
        try {
            if (DotMesh_Model_Node::i()->localNode()) {
                BResponse::i()->redirect(BApp::href());
            }
            $form = BRequest::i()->post('setup');
            $node = DotMesh_Model_Node::i()->setup($form);
        } catch (BException $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
    }

    public function action_index()
    {
        $layout = BLayout::i();
        $hlp = DotMesh_Model_Post::i();
        $orm = $hlp->publicTimelineOrm();
        $timeline = $hlp->fetchTimeline($orm);
        $layout->view('timeline')->set('timeline', $timeline);

        if (BRequest::i()->xhr()) {
            $layout->applyLayout('xhr-timeline');
        } else {
            $layout->applyLayout('/public');
            $title = BLocale::i()->_('Public Timeline');
            $layout->view('head')
                ->addTitle($title)
                ->rss(BApp::href('n/public/feed.rss'));
            $layout->view('timeline')->set(array(
                'title' => $title,
                'feed_uri' => BApp::href('n/public/feed.rss'),
            ));
        }
    }

    public function action_test()
    {
        try {
            $node = DotMesh_Model_Node::i()->find('secure.unirgy.com/dm', true);
            $result = $node->apiClient(array(
                'users' => array('unirgy'),
                'ask_users' => array('unirgy'),
            ));
        } catch (Exception $e) {
            echo "<pre>"; print_r($e); exit;
        }
    }

    public function action_api1_json__POST()
    {
        try {
            $r = BRequest::i();
            $data = $r->json();
            if (!$data) {
                $data = $r->post();
            }
            $result = DotMesh_Model_Node::i()->apiServer($data);
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        BResponse::i()->json($result);
    }

    public function action_search()
    {
        $r = BRequest::i();
        $layout = BLayout::i();
        $q = trim($r->get('q'));
        $node = DotMesh_Model_Node::i()->localNode();
        if ($q[0]==='+') {
            $terms = explode(' ', $q);
            BResponse::i()->redirect($node->uri('u', true).substr($terms[0], 1));
        }
        if ($q[0]==='^') {
            $terms = explode(' ', $q);
            BResponse::i()->redirect($node->uri('t', true).substr($terms[0], 1));
        }
        $hlp = DotMesh_Model_Post::i();
        $timeline = $hlp->fetchTimeline($hlp->searchTimelineOrm($q));
        $layout->view('head')->addTitle($q);
        $layout->view('search')->set('term', $q);
        $layout->view('timeline')->set('timeline', $timeline);

        if ($r->xhr()) {
            $layout->applyLayout('xhr-timeline');
        } else {
            $layout->applyLayout('/search');
            $layout->view('timeline')->set(array(
                'title' => BLocale::i()->_('Searching for: %s', $q),
            ));
        }
    }

    public function action_catch_all()
    {
        $term = BRequest::i()->param('term', true);
        $node = DotMesh_Model_Node::i()->localNode();
        $redirectUrl = null;
        if (($post = $node->post($term))) {
            $redirectUrl = $node->uri('p', true).$term;
        } elseif (($user = $node->user($term))) {
            $redirectUrl = $node->uri('u', true).$term;
        } elseif (($tag = $node->tag($term))) {
            $redirectUrl = $node->uri('t', true).$term;
        }
        if ($redirectUrl) {
            BResponse::i()->redirect($redirectUrl);
        } else {
            $this->forward(true);
        }
    }

    public function action_feed_rss()
    {
        $localNode = DotMesh_Model_Node::i()->localNode();
        $hlp = DotMesh_Model_Post::i();
        switch (BRequest::i()->param('label')) {
        case 'public':
            $orm = $hlp->publicTimelineOrm();
            break;
        default:
            $this->forward(true);
            return;
        }
        $timeline = $hlp->fetchTimeline($orm);
        BResponse::i()->contentType('text/xml')->set($hlp->toRss(array(
            'title' => 'Public Timeline :: '.$localNode->uri(),
            'link' => BApp::href('n/'),
        ), $timeline['rows']));
    }

    public function action_page()
    {
        $page = $this->viewProxy('page');
    }
}
