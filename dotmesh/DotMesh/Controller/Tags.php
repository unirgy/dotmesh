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

class DotMesh_Controller_Tags extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        $layout = BLayout::i();
        $tagname = $r->param('tagname');
        if (!$tagname) {
            BResponse::i()->redirect(BApp::href().'?'.BRequest::i()->rawGet());
        }
        $tag = DotMesh_Model_Node::i()->localNode()->tag($tagname);
        if (!$tag) {
            $this->forward(true);
            return;
        }
        $timeline = DotMesh_Model_Post::i()->fetchTimeline($tag->tagTimelineOrm());
        $layout->view('timeline')->set('timeline', $timeline);

        if ($r->xhr()) {
            $layout->applyLayout('xhr-timeline');
        } else {
            $layout->applyLayout('/tag');
            $layout->view('head')->addTitle($tag->tagname)->canonical($tag->uri(true));;
            $layout->view('tag')->set('tag', $tag);
            $layout->view('timeline')->set(array(
                'title' => BLocale::i()->_('^%s Timeline', $tag->tagname),
                'feed_uri' => $tag->uri(true).'/feed.rss',
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
                $sessUser->subscribeToTag($r->post('tag_uri'), (int)$subscribe);
                $message = ((int)$subscribe ? 'Subscribed to' : 'Unsubscribed from').' ^%s';
                $result = array('status'=>'success', 'message'=>BLocale::i()->_($message, $r->post('tag_uri')));
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
        $tag = $localNode->tag($r->param('tagname'));
        if (!$tag) {
            BResponse::i()->status(404, 'Invalid tag', true);
        }
        $orm = $tag->tagTimelineOrm();
        $timeline = $hlp->fetchTimeline($orm);
        BResponse::i()->contentType('text/xml')->set($hlp->toRss(array(
            'title' => $tag->tagname.' :: Tag Timeline :: '.$localNode->uri(),
            'link' => $tag->uri(true),
        ), $timeline['rows']));
    }
}
