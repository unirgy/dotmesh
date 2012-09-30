<?php
    $u = $this->user;
?>
<h2>My Account</h2>
<form name="my_account" method="post" action="<?=BApp::href('a/')?>" enctype="multipart/form-data">
    <fieldset>
        <label for="username"><?=$this->_('Username')?></label>
        <input type="text" required id="username" name="account[username]" pattern="^[a-zA-Z][a-zA-Z0-9_]+$" title="A valid user name, starting with letter, followed by letters, digits or underscore" placeholder="bond007" readonly value="<?=$this->q($u->username)?>"/>
        <label for="password"><?=$this->_('Password')?></label>
        <input type="password" id="password" name="account[password]" pattern=".{8,}" title="Strong password, minimum 8 characters" placeholder="*****"/>
        <label for="password_confirm"><?=$this->_('Confirm')?></label>
        <input type="password" id="password_confirm" name="account[password_confirm]" pattern=".{8,}" title="Please confirm your password" placeholder="*****"/>
        <label for="email"><?=$this->_('Email')?></label>
        <input type="email" required id="email" name="account[email]" title="A valid email address" placeholder="your@email.com" value="<?=$this->q($u->email)?>"/>
        <label for="firstname"><?=$this->_('First Name')?></label>
        <input type="text" required id="firstname" name="account[firstname]" title="First name" placeholder="Agent" value="<?=$this->q($u->firstname)?>"/>
        <label for="lastname"><?=$this->_('Last Name')?></label>
        <input type="text" required id="lastname" name="account[lastname]" title="Last name" placeholder="Smith" value="<?=$this->q($u->lastname)?>"/>
        <label for="thumb_provider_file"><?=$this->_('File')?></label>
        <input type="radio" id="thumb_provider_file" name="account[thumb_provider]" value="file" <?=$u->thumb_provider=='file'?'checked':''?>/>
        <label for="thumb_provider_gravatar"><?=$this->_('Gravatar')?></label>
        <input type="radio" id="thumb_provider_gravatar" name="account[thumb_provider]" value="gravatar" <?=$u->thumb_provider=='gravatar'?'checked':''?>/>
        <label for="thumb"><?=$this->_('Upload Thumbnail')?></label>
        
        <input type="file" id="thumb" name="thumb" title="Thumbnail"/>
        <button type="submit">Update</button>
    </fieldset>
</form>