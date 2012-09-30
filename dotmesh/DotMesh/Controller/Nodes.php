<?php

class DotMesh_Controller_Nodes extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $this->forward('home', 'DotMesh_Controller_Accounts');
    }
    
    public function action_index__POST()
    {
        try {
            $r = BRequest::i();
            if (!$r->xhr()) {
                BResponse::i()->redirect(BApp::href());
            }
            $result = DotMesh_Model_Node::i()->apiServer($r->json());
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());   
        }
        BResponse::i()->json($result);
    }
    
    public function action_search()
    {
        $r = BRequest::i();
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
        BLayout::i()->applyLayout('/search');
        $hlp = DotMesh_Model_Post::i();
        $timeline = $hlp->fetchTimeline($hlp->searchTimelineOrm($q));
        Blayout::i()->view('timeline')->set('timeline', $timeline);
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
}
