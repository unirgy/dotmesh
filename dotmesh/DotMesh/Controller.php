<?php

class DotMesh_Controller extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        BLayout::i()->applyLayout('/');
        if (DotMesh_Model_User::isLoggedIn()) {
            $timeline = DotMesh_Model_Post::i()->myTimelineOrm()->find_many();
        } else {
            $timeline = array();
        }
        BLayout::i()->view('timeline')->set('timeline', $timeline);
    }

    public function action_catch_all()
    {

    }
}
