<?php

class DotMesh_Controller_Node extends DotMesh_Controler_Abstract
{
    public function action_index()
    {

    }
    
    public function action_index__POST()
    {
        $r = BRequest::i();
        $request = $r->json();
        
    }
}
