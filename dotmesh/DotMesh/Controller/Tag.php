<?php

class DotMesh_Controller_Tag extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        BLayout::i()->applyLayout('/tag');
        Blayout::i()->view('timeline')->set('timeline', DotMesh_Model_Tag::i()->tagTimelineOrm()->find_many());
    }
    
    public function action_json()
    {

    }

    public function action_rss()
    {

    }
}
