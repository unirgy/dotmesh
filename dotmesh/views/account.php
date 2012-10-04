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
        <label for="thumb_provider"><?=$this->_('Avatar Type')?></label>
        <select id="thumb_provider" name="account[thumb_provider]">
<?php foreach (array('gravatar'=>'Gravatar', 'file'=>'Upload a file', 'link'=>'Web Link') as $k=>$v): ?>
            <option value="<?=$k?>" <?=$u->thumb_provider==$k ? 'selected' : ''?>><?=$this->_($v)?></option>
<?php endforeach ?>
        </select>
        <label for="thumb"><?=$this->_('Upload a file')?></label>
        <input type="file" id="thumb" name="thumb" title="Thumbnail"/>
        
        <label for="thumb"><?=$this->_('Avatar web link')?></label>
        <input type="url" id="thumb_uri" name="account[thumb_uri]" title="Avatar web link"/>

        <label for="default_private">Posts are Private by default?</label>
        <input type="checkbox" id="default_private" name="account[preferences][default_private]" value="1" <?=!empty($u->preferences['default_private']) ? 'checked' : ''?> />
        <button type="submit">Update</button>
    </fieldset>
</form>