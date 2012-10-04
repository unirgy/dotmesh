<?php defined('DOTMESH_ROOT_DIR') || die ?>
<h2><?=$this->_('Sign Up')?></h2>
<form name="signup" method="post" action="<?=BApp::href('a/signup')?>">
    <fieldset>
        <label for="username"><?=$this->_('Username')?></label>
        <input type="text" required id="username" name="signup[username]" pattern="^[a-zA-Z][a-zA-Z0-9_]+$" title="A valid user name, starting with letter, followed by letters, digits or underscore" placeholder="bond007 "/>
        <label for="password"><?=$this->_('Password')?></label>
        <input type="password" required id="password" name="signup[password]" pattern=".{8,}" title="Strong password, minimum 8 characters" placeholder="*****"/>
        <label for="password_confirm"><?=$this->_('Confirm')?></label>
        <input type="password" required id="password_confirm" name="signup[password_confirm]" pattern=".{8,}" title="Please confirm your password" placeholder="*****"/>
        <label for="email"><?=$this->_('Email')?></label>
        <input type="email" required id="email" name="signup[email]" title="A valid email address" placeholder="your@email.com"/>
        <label for="firstname"><?=$this->_('First Name')?></label>
        <input type="text" required id="firstname" name="signup[firstname]" title="First name" placeholder="Agent"/>
        <label for="lastname"><?=$this->_('Last Name')?></label>
        <input type="text" required id="lastname" name="signup[lastname]" title="Last name" placeholder="Smith"/>
        <button type="submit">Sign Up</button>
    </fieldset>
</form>