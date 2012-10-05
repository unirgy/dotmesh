<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php if (!defined('DOTMESH_CONFIGURED')): ?>

    <div class="site-main form-col1-layout clearfix page-my-account">
        <h2 class="page-title"><?=$this->_('Please configure config.php using directions in the file')?></h2>
    </div>

    <?php return; endif ?>


<div class="site-main form-col1-layout clearfix page-my-account">
    <h2 class="page-title"><?=$this->_('Setup the Local Node and Admin user')?></h2>
    <form name="setup" method="post" action="<?=BApp::href('n/setup')?>" class="content">
        <div class="col2-set clearfix">
            <div class="col first">
                <fieldset>
                    <h3>This Node Info</h3>
                    <ul class="field-group">
                        <li>
                            <label for="node_uri"><?=$this->_('Node URI')?></label>
                            <input type="text" required id="node_uri" name="setup[node_uri]" title="Format: yourserver.com/optional/path" placeholder="yourserver.com/path_to_dotmesh" value="<?=$this->node_uri?>"/>
                        </li>
                        <li>
                            <label for="is_https">Enable HTTPS?</label>
                            <select id="is_https" name="setup[is_https]">
                                <option value="0"><?=$this->_('No')?></option>
                                <option value="1" <?=$this->is_https?'selected':''?>><?=$this->_('Yes')?></option>
                            </select>
                        </li>
                        <li>
                            <label for="is_rewrite">Enable URL Rewrites?</label>
                            <select id="is_rewrite" name="setup[is_rewrite]">
                                <option value="0"><?=$this->_('No')?></option>
                                <option value="1" <?=$this->is_rewrite?'selected':''?>><?=$this->_('Yes')?></option>
                            </select>
                        </li>
                    </ul>
                </fieldset>
            </div>
            <div class="col last">
                <fieldset>
                    <h3>Admin Account Info</h3>
                    <ul class="field-group">
                        <li>
                            <label for="username"><?=$this->_('Username')?></label>
                            <input type="text" required id="username" name="setup[username]" pattern="^[a-zA-Z][a-zA-Z0-9_]+$" title="A valid user name, starting with letter, followed by letters, digits or underscore" placeholder="bond007 "/>
                        </li>
                        <li>
                            <label for="password"><?=$this->_('Password')?></label>
                            <input type="password" required id="password" name="setup[password]" pattern=".{8,}" title="Strong password, minimum 8 characters" placeholder="*****"/>
                        </li>
                        <li>
                            <label for="password_confirm"><?=$this->_('Confirm')?></label>
                            <input type="password" required id="password_comfirm" name="setup[password_confirm]" pattern=".{8,}" title="Please confirm your password" placeholder="*****"/>
                        </li>
                        <li>
                            <label for="email"><?=$this->_('Email')?></label>
                            <input type="email" required id="email" name="setup[email]" title="A valid email address" placeholder="bond@superduper.com"/>
                        </li>
                        <li>
                            <label for="firstname"><?=$this->_('First Name')?></label>
                            <input type="text" required id="firstname" name="setup[firstname]" title="First name" placeholder="James"/>
                        </li>
                        <li>
                            <label for="lastname"><?=$this->_('Last Name')?></label>
                            <input type="text" required id="lastname" name="setup[lastname]" title="Last name" placeholder="Bond"/>
                        </li>
                    </ul>
                </fieldset>
            </div>
        </div>
        <div class="buttons-group">
            <fieldset>
                <button type="submit"><?=$this->_('Continue')?></button>
            </fieldset>
        </div>
    </form>
</div>