<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
$node = DotMesh_Model_Node::i()->localNode();
$user = DotMesh_Model_User::i()->sessionUser();
?>
<div class="site-header clearfix">
    <?php if (($status = BRequest::i()->get('status'))): ?>
    <div class="messages-container">
        <ul class="messages">
            <li class="<?=$this->q($status)?>"><?=$this->_(BRequest::i()->get('message'))?></li>
        </ul>
    </div>
    <?php endif ?>
    <h1 class="dotmesh-logo"><a href="<?=BApp::href()?>">dotmesh &trade;</a></h1>
<?php if ($node): ?>
   	<a href="<?=BApp::href('n/')?>" class="node-logo tiptip-title" title="<?=$this->_('Node Public Timeline')?>"><span class="icon"></span><?=$this->q($node->uri())?></a>
    <form class="server-search" name="top_search" method="get" action="<?=BApp::href('n/search')?>">
        <fieldset>
            <input type="text" name="q" required placeholder="Search posts, users, tags" class="tiptip-title no-box-shadow" title="<?=$this->_('Examples: post term; +user; ^tag')?>"/>
            <button type="submit" class="icon"><?=$this->_('Search')?></button>
        </fieldset>
    </form>
<?php endif ?>

    <?php if ($user): ?>
    	<nav class="header-links logged-in">
    		<div class="profile popup-parent">
    			<a href="<?=$this->q($user->uri(true))?>" class="user-url"><strong><?=$this->q($user->uri())?></strong></a>
	    		<a href="#" class="avatar"><img src="<?=$this->q($user->thumbUri())?>"/></a>
    			<nav class="popup">
    				<ol>
    					<li class="user-name"><?=$this->_('Hi %s', $user->firstname)?>!</li>
    					<li class="link-received-posts"><a href="<?=BApp::href('a/received')?>"><span class="icon"></span><?=$this->_('Received')?></a></li>
    					<li class="link-sent-posts"><a href="<?=BApp::href('a/sent')?>"><span class="icon"></span><?=$this->_('Sent')?></a></li>
                        <li class="link-private-posts"><a href="<?=BApp::href('a/private')?>"><span class="icon"></span><?=$this->_('Private')?></a></li>
                        <li class="link-starred-posts"><a href="<?=BApp::href('a/starred')?>"><span class="icon"></span><?=$this->_('Starred')?></a></li>
    					<li class="link-logout"><a href="<?=BApp::href('a/logout')?>"><span class="icon"></span><strong><?=$this->_('Log Out')?></strong></a></li>
    				</ol>
    			</nav>
	    	</div>
    		<ol class="buttons-group">
    			<li class="item-new-messages"><a href="#" class="title no-message">0</a></li>
    			<li class="item-settings"><a href="<?=BApp::href('a/')?>" class="title icon tiptip-title" title="<?=$this->_('My Account Settings')?>"></a></li>
    			<li class="item-new-post popup-parent">
    				<a href="<?=BApp::href()?>" class="title icon tiptip-title" title="<?=$this->_('Compose New Post')?>"></a>
<!--
    				<section class="popup private-post" style="display:none">
						<form name="post" method="post" action="<?=BApp::href('p/')?>">
	    					<textarea class="post-input" placeholder="<?=$this->_("What's happening now?")?>"></textarea>
	    					<div class="clearfix"><label for="header_private_post" class="private-post-label"><input type="checkbox" id="header_private_post"/>Private post?</label>
	    					<?=$this->hook('newpost-flags')?></div>
	    					<button type="submit"><span><?=$this->_('Post')?></span></button>
	    				</form>
    				</section>
-->
    			</li>
    		</ol>
       	</nav>
    <?php elseif ($node): ?>
    	<nav class="header-links logged-out">
    		<ul>
	    		<li class="link-login popup-parent hover-delay">
	    			<a href="#" class="title"><?=$this->_('Login / Sign Up')?></a>
	    			<div class="popup">
	    				<header class="popup-title"><?=$this->q($node->uri())?></header>
				        <section class="top-login-form">
                            <form id="top-login" name="top-login" method="post" action="<?=BApp::href('a/login')?>">
                                <fieldset>
                                    <header class="section-title"><?=$this->_('Log into your account')?></header>
                                    <ul class="field-group">
                                        <li><input type="text" name="login[username]" required placeholder="<?=$this->_('Username / Email Address')?>"/></li>
                                        <li><input type="password" name="login[password]" required placeholder="<?=$this->_('Password')?>"/></li>
                                    </ul>
                                    <div class="buttons-group a-right clearfix">
                                        <button type="submit"><?=$this->_('Log In')?></button>
                                        <a href="<?=BApp::href('a/password_recover')?>" id="top-password-trigger"><?=$this->_('Forgot Password?')?></a>
                                    </div>
                                </fieldset>
                            </form>
                            <form id="top-password" name="top-password" method="post" action="<?=BApp::href('a/password_recover')?>">
                                <fieldset>
                                    <header class="section-title"><?=$this->_('Recover your password')?></header>
                                    <ul class="field-group">
                                        <li><input type="text" name="username" required placeholder="<?=$this->_('Username / Email Address')?>"/></li>
                                    </ul>
                                    <div class="buttons-group a-right clearfix">
                                        <button type="submit"><?=$this->_('Recover')?></button>
                                        <a href="<?=BApp::href('a/login')?>" id="top-login-trigger"><?=$this->_('Login')?></a>
                                    </div>
                                </fieldset>
                            </form>
<?php if (($loginAfterHtml = $this->hook('login-after'))): ?>
					        <span class="or"><span>or</span></span>
	                        <?=$loginAfterHtml?>
<?php endif ?>
				        </section>
				        <section class="top-registration-form">
					        <form name="top_signup" method="post" action="<?=BApp::href('a/signup')?>">
					            <fieldset>
					            	<header class="section-title"><?=$this->_('Sign up for an account')?></header>
					                <ul class="field-group">
					                	<li><input type="text" name="signup[username]" required placeholder="<?=$this->_('Username')?>" pattern="^[a-zA-Z][a-zA-Z0-9_]+$" title="<?=$this->_('A valid user name, starting with a letter, followed by letters, digits or underscore')?>"/></li>
					                	<li><input type="password" name="signup[password]" required placeholder="<?=$this->_('Password')?>" pattern=".{8,}" title="<?=$this->_('Strong password, minimum 8 characters')?>"/></li>
					                	<li><input type="password" name="signup[password_confirm]" required placeholder="<?=$this->_('Confirm Password')?>" pattern=".{8,}" title="<?=$this->_('Please confirm your password')?>"/></li>
					                	<li><input type="email" name="signup[email]" required placeholder="<?=$this->_('Email Address')?>" title="<?=$this->_('A valid email address')?>"/></li>
					                	<li><input type="text" name="signup[firstname]" required placeholder="<?=$this->_('First Name')?>" title="<?=$this->_('First name')?>"/></li>
					                	<li><input type="text" name="signup[lastname]" required placeholder="<?=$this->_('Last Name')?>" title="<?=$this->_('Last name')?>"/></li>
					                </ul>
<?php if (($tncUri = BConfig::i()->get('modules/DotMesh/tnc_uri'))): ?>
					                <div class="terms-line clearfix">
					                	<input type="checkbox" id="agree_tnc" name="signup[agree_tnc]" required/> <label for="agree_tnc"><?=$this->q($this->_('I agree to the <a href="%s">terms & conditions', array($tncUri)))?></a></label>
					                </div>
<?php endif ?>
					                <div class="buttons-group clearfix">
					                	<button type="submit"><?=$this->_('Sign Up')?></button>
					                </div>
					            </fieldset>
					        </form>
				        </section>
			        </div>
	    		</li>
			</ul>
		</nav>
    <?php endif ?>

</div>