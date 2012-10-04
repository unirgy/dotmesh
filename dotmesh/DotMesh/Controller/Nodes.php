<?php

class DotMesh_Controller_Nodes extends DotMesh_Controler_Abstract
{
    public function action_404()
    {
        BLayout::i()->applyLayout('404');
    }
    
    public function action_503()
    {
        BLayout::I()->applyLayout('503');
    }
        
    public function action_setup()
    {
        if (DotMesh_Model_Node::i()->localNode()) {
            BResponse::i()->redirect(BApp::href());
        }
        $r = BRequest::i();
        BLayout::i()->view('setup')->set(array(
            'node_uri' => trim($r->httpHost().'/'.$r->webRoot(), '/'),
            'is_https' => $r->https(),
            'is_modrewrite' => $r->modRewriteEnabled(),
        ));
        BLayout::i()->applyLayout('/setup');
    }

    public function action_setup__POST()
    {
        $redirectUrl = BApp::href();
        try {
            if (DotMesh_Model_Node::i()->localNode()) {
                BResponse::i()->redirect(BApp::href());
            }
            $form = BRequest::i()->post('setup');
            $node = DotMesh_Model_Node::i()->setup($form);
        } catch (BException $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
    }

    public function action_index()
    {
        $hlp = DotMesh_Model_Post::i();
        $orm = $hlp->publicTimelineOrm();
        $timeline = $hlp->fetchTimeline($orm);
        BLayout::i()->view('timeline')->set('timeline', $timeline);
        
        if (BRequest::i()->xhr()) {
            BLayout::i()->applyLayout('xhr-timeline');
        } else {
            BLayout::i()->applyLayout('/public');
        }
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
        $hlp = DotMesh_Model_Post::i();
        $timeline = $hlp->fetchTimeline($hlp->searchTimelineOrm($q));
        Blayout::i()->view('timeline')->set('timeline', $timeline);
        
        if ($r->xhr()) {
            BLayout::i()->applyLayout('xhr-timeline');
        } else {
            BLayout::i()->applyLayout('/search');
        }
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
