<?php

class DotMesh_Controller_User extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        BLayout::i()->applyLayout('/user');
        Blayout::i()->view('timeline')->set('timeline', DotMesh_Model_User::i()->userTimelineOrm()->find_many());
    }
    
    public function action_json()
    {

    }

    public function action_rss()
    {

    }
}