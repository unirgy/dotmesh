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

    public function action_api1_json()
    {

    }

    public function action_feed_rss()
    {

    }
}
