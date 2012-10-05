<?php

class DotMesh_Controller_Tags extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        $layout = BLayout::i();
        $tagname = $r->param('tagname');
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
            $layout->view('head')->addTitle($tag->tagname);
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
                $message = ((int)$subscribe ? 'Subscribed to' : 'Unsubscribed from').' +%s';
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

    }
}
