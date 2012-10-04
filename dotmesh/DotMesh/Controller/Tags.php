<?php

class DotMesh_Controller_Tags extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        $tagname = $r->param('tagname');
        $tag = DotMesh_Model_Node::i()->localNode()->tag($tagname);
        if (!$tag) {
            $this->forward(true);
            return;
        }
        $timeline = DotMesh_Model_Post::i()->fetchTimeline($tag->tagTimelineOrm());
        BLayout::i()->view('timeline')->set('timeline', $timeline);
        
        if ($r->xhr()) {
            BLayout::i()->applyLayout('xhr-timeline');
        } else {
            BLayout::i()->applyLayout('/tag');
            BLayout::i()->view('tag')->set('tag', $tag);
        }
    }
    
    public function action_api1_json()
    {

    }

    public function action_feed_rss()
    {

    }
}
