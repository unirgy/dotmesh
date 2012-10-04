<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php if (!BConfig::i()->get('modules/DotMesh/configured')): ?>

<h2><?=$this->_('Please configure config.php using directions in the file')?></h2>

<?php return; endif ?>



<h2><?=$this->_('Setup the node and admin user')?></h2>
<form name="setup" method="post" action="<?=BApp::href('n/setup')?>">
    <fieldset>
        <label for="node_uri"><?=$this->_('This Node URI')?></label>
        <input type="text" required id="node_uri" name="setup[node_uri]" title="Format: yourserver.com/optional/path" placeholder="yourserver.com/path_to_dotmesh" value="<?=$this->node_uri?>"/>
        <label for="is_https">Enable HTTPS?</label>
        <select id="is_https" name="setup[is_https]">
            <option value="0"><?=$this->_('No')?></option>
            <option value="1" <?=$this->is_https?'selected':''?>><?=$this->_('Yes')?></option>
        </select>
        <label for="is_modrewrite">Enable URL Rewrites?</label>
        <select id="is_modrewrite" name="setup[is_modrewrite]">
            <option value="0"><?=$this->_('No')?></option>
            <option value="1" <?=$this->is_modrewrite?'selected':''?>><?=$this->_('Yes')?></option>
        </select>
    </fieldset>
    <fieldset>
        <label for="username"><?=$this->_('Username')?></label>
        <input type="text" required id="username" name="setup[username]" pattern="^[a-zA-Z][a-zA-Z0-9_]+$" title="A valid user name, starting with letter, followed by letters, digits or underscore" placeholder="bond007 "/>
        <label for="password"><?=$this->_('Password')?></label>
        <input type="password" required id="password" name="setup[password]" pattern=".{8,}" title="Strong password, minimum 8 characters" placeholder="*****"/>
        <label for="password_confirm"><?=$this->_('Confirm')?></label>
        <input type="password" required id="password_comfirm" name="setup[password_confirm]" pattern=".{8,}" title="Please confirm your password" placeholder="*****"/>
        <label for="email"><?=$this->_('Email')?></label>
        <input type="email" required id="email" name="setup[email]" title="A valid email address" placeholder="bond@superduper.com"/>
        <label for="name"><?=$this->_('Name')?></label>
        <input type="text" required id="name" name="setup[name]" title="Your name" placeholder="James Bond"/>
    </fieldset>
    <fieldset>
        <button type="submit">Sign Up</button>
    </fieldset>
</form>