<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
$node = DotMesh_Model_Node::i()->localNode();
$user = DotMesh_Model_User::i()->sessionUser();
?>
<div class="site-header clearfix">
    <h1 class="dotmesh-logo"><a href="<?=BApp::href()?>">dotmesh &trade;</a></h1>
   	<a href="<?=BApp::href('n/')?>" class="node-logo"><span class="icon"></span><?=$this->q($node->uri())?></a>
    <form class="server-search" name="top_search" method="get" action="<?=BApp::href('n/search')?>">
        <fieldset>
            <input type="text" name="q" required placeholder="Search posts, users, tags"/>
            <button type="submit"><?=$this->_('Search')?></button>
        </fieldset>
    </form>

    <?php if ($user): ?>
    	<nav class="header-links logged-in">
    		<div class="profile popup-parent">
    			<a href="<?=$this->q($user->uri(true))?>" class="user-url"><strong><?=$this->q($user->uri())?></strong></a>
	    		<a href="#" class="avatar"><img src="<?=$this->q($user->thumbUri())?>"/></a>
    			<section class="popup">
    				<ol>
    					<li class="user-name"><?=$this->_('Hi %s', $user->firstname)?>!</li>
    					<li class="link-received-posts"><a href="#"><span class="icon"></span>Received</a></li>
    					<li class="link-sent-posts"><a href="#"><span class="icon"></span>Sent</a></li>
                        <li class="link-private-posts"><a href="#"><span class="icon"></span>Private</a></li>
                        <li class="link-starred-posts"><a href="#"><span class="icon"></span>Starred</a></li>
    					<li class="link-logout"><a href="<?=BApp::href('a/logout')?>"><span class="icon"></span><strong><?=$this->_('Log Out')?></strong></a></li>
    				</ol>
    			</section>
	    	</div>
    		<ol class="buttons-group">
    			<li class="item-new-messages"><a href="#" class="title no-message">0</a></li>
    			<li class="item-settings"><a href="<?=BApp::href('a/')?>" class="title icon"></a></li>
    			<li class="item-new-post popup-parent">
    				<a href="#" class="title icon"></a>
    				<section class="popup private-post">
						<form name="post" method="post" action="<?=BApp::href('p/')?>">
	    					<textarea class="post-input" placeholder="What's happening now?"></textarea>
	    					<div class="clearfix"><label for="header_private_post" class="private-post-label"><input type="checkbox" id="header_private_post"/>Private post?</label>
	    					<?=$this->hook('newpost-flags')?></div>
	    					<button type="submit"><span>Post</span></button>
	    				</form>
    				</section>
    			</li>
    		</ol>
       	</nav>
    <?php else: ?>
    	<nav class="header-links logged-out">
    		<ul>
	    		<li class="link-login popup-parent">
	    			<a href="#" class="title">Login / Sign Up</a>
	    			<div class="popup">
	    				<header class="popup-title"><?=$this->q($node->uri())?></header>
				        <section class="top-login-form">
					        <form name="top_login" method="post" action="<?=BApp::href('a/login')?>">
					            <fieldset>
					            	<header class="section-title">Log into your account</header>
					                <ul class="field-group">
					                	<li><input type="text" name="login[username]" required placeholder="Username / Email Address"/></li>
					                	<li><input type="password" name="login[password]" required placeholder="Password"/></li>
					                </ul>
					                <div class="buttons-group a-right clearfix">
					                	<button type="submit"><?=$this->_('Log In')?></button>
					                	<a href="<?=BApp::href('a/password_recover')?>"><?=$this->_('Forgot Password?')?></a>
					                </div>
					            </fieldset>
					        </form>
					        <span class="or"><span>or</span></span>
	                        <?=$this->hook('login-after')?>
				        </section>
				        <section class="top-registration-form">
					        <form name="top_signup" method="post" action="<?=BApp::href('a/signup')?>">
					            <fieldset>
					            	<header class="section-title">Sign up for an account</header>
					                <ul class="field-group">
					                	<li><input type="text" name="signup[username]" required placeholder="Username"/></li>
					                	<li><input type="password" name="signup[password]" required placeholder="Password"/></li>
					                	<li><input type="password" name="signup[password_confirm]" required placeholder="Confirm Password"/></li>
					                	<li><input type="text" name="signup[email]" required placeholder="Email Address"/></li>
					                	<li><input type="text" name="signup[firstname]" required placeholder="First Name"/></li>
					                	<li><input type="text" name="signup[lastname]" required placeholder="Last Name"/></li>
					                </ul>
					                <div class="terms-line clearfix">
					                	<input type="checkbox"/> I agree to the <a href="#">terms of conditions</a>
					                </div>
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
    <?php if (($status = BRequest::i()->get('status'))): ?>
        <ul class="messages">
            <li class="<?=$this->q($status)?>"><?=$this->_(BRequest::i()->get('message'))?></li>
        </ul>
    <?php endif ?>