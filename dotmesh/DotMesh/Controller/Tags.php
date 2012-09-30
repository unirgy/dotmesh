<?php

class DotMesh_Controller_Tags extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $tagname = BRequest::i()->param('tagname');
        $tag = DotMesh_Model_Node::i()->localNode()->tag($tagname);
        $timeline = DotMesh_Model_Post::i()->fetchTimeline($tag->tagTimelineOrm());
        BLayout::i()->applyLayout('/tag');
        BLayout::i()->view('tag')->set('tag', $tag);
        BLayout::i()->view('timeline')->set('timeline', $timeline);
    }
    
    public function action_json()
    {

    }

    public function action_rss()
    {

    }
}
