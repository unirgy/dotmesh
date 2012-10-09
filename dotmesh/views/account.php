<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
    $u = $this->user;
?>
<div class="site-main form-col1-layout clearfix page-my-account">
	<h2 class="page-title"><?=$this->_('My Account')?></h2>
	<form name="my_account" method="post" action="<?=BApp::href('a/')?>" enctype="multipart/form-data" class="content">
		<div class="col2-set clearfix">
		    <div class="col first">
		    	<fieldset>
		    		<div class="avatar">
		    			<img src="<?=$this->q($u->thumbUri())?>"/>
		    			<!--<a href="#" class="icon icon-delete"><?=$this->_('Delete image')?></a>-->
		    		</div>
			    	<ul class="field-group">
			    		<li>
					        <label for="thumb_provider"><?=$this->_('Avatar Type')?></label>
					        <select id="thumb_provider" name="account[thumb_provider]">
					<?php foreach (array('gravatar'=>'Gravatar', 'file'=>'File Upload', 'link'=>'Web Link') as $k=>$v): ?>
					            <option value="<?=$k?>" <?=$u->thumb_provider==$k ? 'selected' : ''?>><?=$this->_($v)?></option>
					<?php endforeach ?>
					        </select>
					  	</li>
			    		<li id="thumb-fileupload">
					        <label for="thumb"><?=$this->_('Upload a file')?></label>
					        <input type="file" id="thumb" name="thumb" title="Thumbnail"/>
					  	</li>
			    		<li id="thumb-weblink">
					        <label for="thumb"><?=$this->_('Avatar web link')?></label>
					        <input type="url" id="thumb_uri" name="account[thumb_uri]" title="<?=$this->_('Avatar web link')?>" value="<?=$this->q($u->thumb_uri)?>"/>
					  	</li>
					</ul>
		  		</fieldset>
		    </div>
		    <div class="col last">
		  		<fieldset>
		    		<h3><?=$this->_('Account Info')?></h3>
			    	<ul class="field-group">
			    		<li>
					        <label for="username"><?=$this->_('Username')?></label>
					        <input type="text" required id="username" name="account[username]" pattern="^[a-zA-Z][a-zA-Z0-9_]+$" title="<?=$this->_('A valid user name, starting with letter, followed by letters, digits or underscore')?>" placeholder="bond007" readonly value="<?=$this->q($u->username)?>"/>
					  	</li>
			    		<li>
			    			<button type="button" name="do" value="password_reset" onclick="return this.form.submit()">Reset Password</button>
					  	</li>
			    		<li>
					        <label for="email"><?=$this->_('Email')?></label>
					        <input type="email" required id="email" name="account[email]" title="<?=$this->_('A valid email address')?>" placeholder="your@email.com" value="<?=$this->q($u->email)?>"/>
					  	</li>
			    		<li>
					        <label for="firstname"><?=$this->_('First Name')?></label>
					        <input type="text" required id="firstname" name="account[firstname]" title="<?=$this->_('First name')?>" placeholder="Agent" value="<?=$this->q($u->firstname)?>"/>
					  	</li>
                        <li>
                            <label for="lastname"><?=$this->_('Last Name')?></label>
                            <input type="text" required id="lastname" name="account[lastname]" title="<?=$this->_('Last name')?>" placeholder="Smith" value="<?=$this->q($u->lastname)?>"/>
                          </li>
                        <li>
                            <label for="short_bio"><?=$this->_('Short Bio')?></label>
                            <textarea id="short_bio" name="account[short_bio]" title="<?=$this->_('Short Bio')?>"><?=$this->q($u->short_bio)?></textarea>
                          </li>
			    	</ul>
			    </fieldset>
		  		<fieldset>
		    		<h3><?=$this->_('Settings')?></h3>
			    	<ul class="field-group">
			    		<li>
					        <label for="default_private"><input type="checkbox" id="default_private" name="account[preferences][default_private]" value="1" <?=!empty($u->preferences['default_private']) ? 'checked' : ''?> /><?=$this->_('Posts are Private by default?')?></label>
					  	</li>
			    	</ul>
			    </fieldset>
			</div>
	    </div>
	    <div class="buttons-group">
	    	<button type="submit" name="do" value="update"><?=$this->_('Update')?></button>
	    </div>
	</form>
</div>