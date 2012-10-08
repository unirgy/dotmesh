<?php

/**
* This file is part of DotMesh.
*
* DotMesh is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* any later version.
*
* Foobar is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with DotMesh.  If not, see <http://www.gnu.org/licenses/>.
*
* @package DotMesh (tm)
* @link http://dotmesh.org
* @author Boris Gurvich <boris@unirgy.com>
* @copyright (c) 2012 Boris Gurvich
* @license http://www.gnu.org/licenses/gpl.txt
*/

class DotMesh_Controller_Accounts extends DotMesh_Controler_Abstract
{
    public function action_index()
    {
        if (!DotMesh_Model_User::isLoggedIn()) {
            BResponse::i()->redirect(BApp::href());
        }
        BLayout::i()->applyLayout('/account');
        BLayout::i()->view('head')->addTitle(BLocale::i()->_('My Account'));
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
            switch ($r->post('do')) {
            case 'password_reset':
                $user->recoverPassword();
                $result = array('status'=>'success', 'message'=>'Password recovery instructions have been sent to your email');
                break;
            default:
                $user->updateFromPost($r->post('account'));
                $result = array('status'=>'success', 'message'=>'Your account changes were saved');
            }
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

    public function action_my()
    {
        if (!DotMesh_Model_User::i()->isLoggedIn()) {
            $this->forward('index', 'DotMesh_Controller_Nodes');
            return;
        }
        $layout = BLayout::i();
        $orm = DotMesh_Model_User::i()->myTimelineOrm();
        $timeline = DotMesh_Model_Post::i()->fetchTimeline($orm);
        $layout->view('timeline')->set('timeline', $timeline);

        if (BRequest::i()->xhr()) {
            $layout->applyLayout('xhr-timeline');
        } else {
            $layout->applyLayout('/my');

            $user = DotMesh_Model_User::i()->sessionUser();
            $title = BLocale::i()->_(DotMesh::i()->folderTitle('my'));
            $layout->view('head')->addTitle($title);
            $layout->view('my-posts')->set(array(
                'folder' => 'my',
            ));
            $layout->view('timeline')->set(array(
                'title' => $title,
                'feed_uri' => $user->feedUri('my'),
            ));
        }
    }

    protected function _folderTimeline($folder)
    {
        if (!DotMesh_Model_User::i()->isLoggedIn()) {
            $this->forward(true);
            return;
        }
        $layout = BLayout::i();
        $orm = DotMesh_Model_User::i()->myFolderTimelineOrm($folder);
        $timeline = DotMesh_Model_Post::i()->fetchTimeline($orm);
        $layout->view('timeline')->set('timeline', $timeline);

        if (BRequest::i()->xhr()) {
            $layout->applyLayout('xhr-timeline');
        } else {
            $layout->applyLayout('/my');

            $user = DotMesh_Model_User::i()->sessionUser();
            $title = DotMesh::i()->folderTitle($folder);
            $layout->view('head')->addTitle($title);
            $layout->view('my-posts')->set(array(
                'title' => BLocale::i()->_($title),
                'folder' => $folder,
            ));
            $layout->view('timeline')->set(array(
                'title' => $title,
                'feed_uri' => $user->feedUri($folder),
            ));
        }
    }

    public function action_received()
    {
        $this->_folderTimeline('received');
    }

    public function action_sent()
    {
        $this->_folderTimeline('sent');
    }

    public function action_private()
    {
        $this->_folderTimeline('private');
    }

    public function action_starred()
    {
        $this->_folderTimeline('starred');
    }

    public function action_feed_rss()
    {
        $r = BRequest::i();
        if (!$r->get('u') || !$r->get('h')) {
            BResponse::i()->status(401, 'Missing user name or password', true);
        }
        $localNode = DotMesh_Model_Node::i()->localNode();
        $user = $localNode->user($r->get('u'));
        if (!$user || $r->get('h')!==hash('sha256', $user->password_hash)) {
            BResponse::i()->status(401, 'Invalid user or password', true);
        }
        $hlp = DotMesh_Model_Post::i();
        $label = BRequest::i()->param('label');
        switch ($label) {
        case 'my':
            $orm = DotMesh_Model_User::i()->myTimelineOrm($user->id);
            $title = 'My Timeline :: '.$user->username.' :: '.$localNode->uri();
            break;
        case 'received': case 'sent': case 'private': case 'starred':
            $orm = DotMesh_Model_User::i()->myFolderTimelineOrm($label, $user->id);
            $title = ucwords($label).' :: '.$user->username.' :: '.$localNode->uri();
            break;
        default:
            $this->forward(true);
            return;
        }
        $timeline = $hlp->fetchTimeline($orm);
        $rssXml = $hlp->toRss(array(
            'title' => $title,
            'link' => BApp::href('a/'.$label),
        ), $timeline['rows']);
        BResponse::i()->contentType('text/xml')->set($rssXml);
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

    public function action_password_recover()
    {
        BLayout::i()->applyLayout('/password_recover');
    }

    public function action_password_recover__POST()
    {
        try {
            $r = BRequest::i();
            $redirectUrl = BApp::href();
            $localNode = DotMesh_Model_Node::i()->localNode();
            $user = DotMesh_Model_User::i()->orm('u')->where(array(
                'node_id' => $localNode->id,
                (strpos($n, '@')!==false ? 'email' : 'username') => $r->post('username'),
            ))->find_one();
            if ($user) {
                $user->recoverPassword();
            }
            $result = array('status'=>'success', 'message'=>'Password recovery instructions have been sent to your email');
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        if ($r->xhr()) {
            BResponse::i()->json($result);
        } else {
            BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
        }
    }

    public function action_password_reset()
    {
        $r = BRequest::i();
        if (!$r->get('u') || !$r->get('n')) {
            BResponse::i()->redirect(BApp::href().'?status=error&message='.urlencode('Missing user name or token'));
        }
        $user = DotMesh_Model_Node::i()->localNode()->user($r->get('u'));
        if ($user->password_nonce!==$r->get('n')) {
            BResponse::i()->redirect(BApp::href().'?status=error&message='.urlencode('Invalid or expired token'));
        }

        BLayout::i()->applyLayout('/password_reset');
        BLayout::i()->view('password-reset')->set('user', $user);
    }

    public function action_password_reset__POST()
    {
        try {
            $r = BRequest::i();
            $redirectUrl = BApp::href();
            $form = $r->post('reset');
            if (empty($form['username']) || empty($form['password_nonce'])
                || empty($form['password']) || empty($form['password_confirm'])
            ) {
                throw new BException('Incomplete form data');
            }
            if ($form['password']!==$form['password_confirm']) {
                throw new BException('Password does not match confirmation');
            }
            $localNode = DotMesh_Model_Node::i()->localNode();
            $user = DotMesh_Model_User::i()->orm('u')->where(array(
                'node_id' => $localNode->id,
                (strpos($n, '@')!==false ? 'email' : 'username') => $form['username'],
            ))->find_one();
            if ($user) {
                if ($user->password_nonce!==$form['password_nonce']) {
                    throw new BException('Invalid or expired password nonce token');
                }
                $user->resetPassword($form['password']);
            }
            $result = array('status'=>'success', 'message'=>'Your password has been reset');
        } catch (Exception $e) {
            $result = array('status'=>'error', 'message'=>$e->getMessage());
        }
        if ($r->xhr()) {
            BResponse::i()->json($result);
        } else {
            BResponse::i()->redirect(BUtil::setUrlQuery($redirectUrl, $result));
        }
    }

    public function action_logout()
    {
        if (($user = DotMesh_Model_User::i()->sessionUser())) {
            $user->logout();
        }
        BResponse::i()->redirect(BApp::href());
    }

    public function action_pub_users()
    {
        $user = DotMesh_Model_User::i()->sessionUser();
        if (!$user) {
            BReseponse::i()->redirect(BApp::href());
        }
        $limit = 10;
        $rows = $user->subscribedToUsers($limit);
        BLayout::i()->view('user-list')->set(array(
            'title' => 'Subscribed to users',
            'list' => array('rows'=>$rows, 'is_last_page'=>sizeof($rows)<$limit),
        ));
        BLayout::i()->applyLayout('/pub_users');
    }

    public function action_pub_tags()
    {
        $user = DotMesh_Model_User::i()->sessionUser();
        if (!$user) {
            BReseponse::i()->redirect(BApp::href());
        }
        $limit = 10;
        $rows = $user->subscribedToTags($limit);
        BLayout::i()->view('tag-list')->set(array(
            'title' => 'Subscribed to tags',
            'list' => array('rows'=>$rows, 'is_last_page'=>sizeof($rows)<$limit),
        ));
        BLayout::i()->applyLayout('/pub_tags');
    }

    public function action_sub_users()
    {
        $user = DotMesh_Model_User::i()->sessionUser();
        if (!$user) {
            BReseponse::i()->redirect(BApp::href());
        }
        $limit = 10;
        $rows = $user->subscribers($limit);
        BLayout::i()->view('user-list')->set(array(
            'title' => 'Subscribers',
            'list' => array('rows'=>$rows, 'is_last_page'=>sizeof($rows)<$limit),
        ));
        BLayout::i()->applyLayout('/sub_users');
    }
}
