<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
    $u = $this->user;
?>
<div class="site-main form-col1-layout clearfix page-my-account">
	<h2 class="page-title">My Account</h2>
	<form name="my_account" method="post" action="<?=BApp::href('a/')?>" enctype="multipart/form-data" class="content">
		<div class="col2-set clearfix">
		    <div class="col first">
		    	<fieldset>
		    		<div class="avatar">
		    			<img src="<?=$this->q($u->thumbUri())?>"/>
		    			<a href="#" class="icon icon-delete">Delete image</a>
		    		</div>
			    	<ul class="field-group">
			    		<li>
					        <label for="thumb_provider"><?=$this->_('Avatar Type')?></label>
					        <select id="thumb_provider" name="account[thumb_provider]">
					<?php foreach (array('gravatar'=>'Gravatar', 'file'=>'Upload a file', 'link'=>'Web Link') as $k=>$v): ?>
					            <option value="<?=$k?>" <?=$u->thumb_provider==$k ? 'selected' : ''?>><?=$this->_($v)?></option>
					<?php endforeach ?>
					        </select>
					  	</li>
			    		<li>
					        <label for="thumb"><?=$this->_('Upload a file')?></label>
					        <input type="file" id="thumb" name="thumb" title="Thumbnail"/>
					  	</li>
			    		<li>
					        <label for="thumb"><?=$this->_('Avatar web link')?></label>
					        <input type="url" id="thumb_uri" name="account[thumb_uri]" title="Avatar web link"/>
					  	</li>
					</ul>
		  		</fieldset>
		    </div>
		    <div class="col last">
		  		<fieldset>
		    		<h3>Account Info</h3>
			    	<ul class="field-group">
			    		<li>
					        <label for="username"><?=$this->_('Username')?></label>
					        <input type="text" required id="username" name="account[username]" pattern="^[a-zA-Z][a-zA-Z0-9_]+$" title="A valid user name, starting with letter, followed by letters, digits or underscore" placeholder="bond007" readonly value="<?=$this->q($u->username)?>"/>
					  	</li>
			    		<li>
			    			<label class="inline">Password:</label> ********  <a href="#">Reset</a>
					  	</li>
			    		<li>
					        <label for="email"><?=$this->_('Email')?></label>
					        <input type="email" required id="email" name="account[email]" title="A valid email address" placeholder="your@email.com" value="<?=$this->q($u->email)?>"/>
					  	</li>
			    		<li>
					        <label for="firstname"><?=$this->_('First Name')?></label>
					        <input type="text" required id="firstname" name="account[firstname]" title="First name" placeholder="Agent" value="<?=$this->q($u->firstname)?>"/>
					  	</li>
			    		<li>
					        <label for="lastname"><?=$this->_('Last Name')?></label>
					        <input type="text" required id="lastname" name="account[lastname]" title="Last name" placeholder="Smith" value="<?=$this->q($u->lastname)?>"/>
					  	</li>
			    	</ul>
			    </fieldset>
		  		<fieldset>
		    		<h3>Settings</h3>
			    	<ul class="field-group">
			    		<li>
					        <label for="default_private"><input type="checkbox" id="default_private" name="account[preferences][default_private]" value="1" <?=!empty($u->preferences['default_private']) ? 'checked' : ''?> />Posts are Private by default?</label>
					  	</li>
			    	</ul>
			    </fieldset>
			</div>
	    </div>
	    <div class="buttons-group">
	    	<button type="submit">Update</button>
	    </div>
	</form>
</div>