<?php

class DotMesh_Controller_Post extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        $r = BRequest::i();
        $hlp = DotMesh_Model_Post::i();
        $postname = $r->param('postname');
        if ($postname) {
            $post = $hlp->load($postname, 'postname');
            if (!$post) {
                throw new BException('Invalid post identifier');
            }
        }
        BLayout::i()->applyLayout('/post');
        Blayout::i()->view('timeline')->set('timeline', DotMesh_Model_Post::i()->threadTimelineOrm()->find_many());
    }
    
    public function action_index__POST()
    {
        try {
            $redirectUrl = BApp::href();
            if (!DotMesh_Model_User::isLoggedIn()) {
                throw new BException('Not logged in');
            }
            $r = BRequest::i();
            $hlp = DotMesh_Model_Post::i();
            $postname = $r->param('postname');
            if ($postname) {
                $post = $hlp->load($postname, 'postname');
                if (!$post) {
                    throw new BException('Invalid post identifier');
                }
            }
            $do = $r->post('do');
            switch ($do) {
            case 'new':
                if ($post) {
                    throw new BException('Invalid post action');
                }
                $post = $hlp->submitNewPost($r->post());
                $result = $post->result;
                break;
            case 'star': case 'un-star':
                $post->submitFeedback('star', $do=='star' ? 1 : 0);
                break;
            case 'report': case 'un-report':
                $post->submitFeedback('report', $do=='report' ? 1 : 0);
                break;
            case 'score-up': case 'un-score-up':
                $post->submitFeedback('score', $do=='score-up' ? 1 : 0);
                break;
            case 'score-down': case 'un-score-down':
                $post->submitFeedback('score', $do=='score-down' ? -1 : 0);
                break;
            case 'delete':
                if (DotMesh_Model_User::sessionUserId()!==$post->user_id) {
                    throw new BException('Post does not belong to logged in user');
                }
                $post->delete();
                break;
            }
            $result['status'] = 'success';
            $result['message'] = 'Your post has been submited';
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

    public function action_json()
    {

    }

    public function action_rss()
    {

    }
}

