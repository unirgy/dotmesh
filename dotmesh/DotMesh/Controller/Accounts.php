<?php

class DotMesh_Controller_Accounts extends DotMesh_Controler_Abstract
{
    public function action_login()
    {
        BLayout::i()->applyLayout('/login');
    }

    public function action_login__POST()
    {
        $r = BRequest::i();
        $redirectUrl = $r->referrer() ? $r->referrer() : BApp::href();
        try {
            if ($r->xhr()) {
                $form = $r->json();
            } else {
                $form = $r->post('login');
            }
            if (empty($form['username']) || empty($form['password'])) {
                throw new BException('Missing username or password');
            }
            $user = DotMesh_Model_User::i()->authenticate($form['username'], $form['password']);
            if (!$user) {
                throw new BException('Invalid username or password');
            }
            $user->login();
            $result = array('status'=>'success', 'message'=>'Login successful');
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
    
    public function action_index()
    {
        if (!DotMesh_Model_User::isLoggedIn()) {
            BResponse::i()->redirect(BApp::href());
        }
        BLayout::i()->applyLayout('/account');
        BLayout::i()->view('account')->set('user', DotMesh_Model_User::i()->sessionUser());
    }
    
    public function action_index__POST()
    {
        $r = BRequest::i();
        $redirectUrl = $r->referrer() ? $r->referrer() : BApp::href();
        try {
            if (!DotMesh_Model_User::isLoggedIn()) {
                throw new BException('Not logged in');
            }
            $user = DotMesh_Model_User::i()->sessionUser();
            $user->updateFromPost($r->post('account'));
            $result = array('status'=>'success', 'message'=>'Your account changes were saved');
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
    
    public function action_home()
    {
        if (!DotMesh_Model_User::i()->isLoggedIn()) {
            $this->forward('index', 'DotMesh_Controller_Nodes');
            return;
        }
        $hlp = DotMesh_Model_Post::i();
        $orm = $hlp->myTimelineOrm();
        $timeline = $hlp->fetchTimeline($orm);
        BLayout::i()->view('timeline')->set('timeline', $timeline);
        
        if (BRequest::i()->xhr()) {
            BLayout::i()->applyLayout('xhr-timeline');
        } else {
            BLayout::i()->applyLayout('/');
        }
    }

    public function action_signup()
    {
        BLayout::i()->applyLayout('/signup');
    }

    public function action_signup__POST()
    {
        $r = BRequest::i();
        $redirectUrl = BApp::href();
        try {
            if ($r->xhr()) {
                $form = $r->json();
            } else {
                $form = $r->post('signup');
            }
            $user = DotMesh_Model_User::i()->signup($form)->login();
            $result = array('status'=>'success', 'message'=>'Sign up successful');
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

    public function action_password_recover()
    {
        BLayout::i()->applyLayout('/password_recover');
    }

    public function action_password_recover__POST()
    {

        BResponse::i()->redirect(BApp::href());
    }

    public function action_password_reset()
    {
        BLayout::i()->applyLayout('/password_reset');
    }

    public function action_password_reset__POST()
    {

        BResponse::i()->redirect(BApp::href());
    }

    public function action_logout()
    {
        if (($user = DotMesh_Model_User::i()->sessionUser())) {
            $user->logout();
        }
        BResponse::i()->redirect(BApp::href());
    }
}
