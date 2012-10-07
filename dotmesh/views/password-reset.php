
<div class="site-main form-col1-layout clearfix page-password-reset">
    <h2 class="page-title"><?=$this->_('Reset your password')?></h2>
    <form name="setup" method="post" action="<?=BApp::href('a/password_reset')?>" class="content">
        <fieldset>
            <h3>Please enter a new password for your account</h3>
            <ul class="field-group">
                <li>
                    <label for="username"><?=$this->_('Username')?></label>
                    <input type="text" readonly id="username" name="reset[username]" value="<?=$this->q($this->user->username)?>"/>
                </li>
                <li>
                    <label for="password"><?=$this->_('New Password')?></label>
                    <input type="password" required id="password" name="reset[password]" pattern=".{8,}" title="Strong password, minimum 8 characters"/>
                </li>
                <li>
                    <label for="password_confirm"><?=$this->_('Confirm New Password')?></label>
                    <input type="password" required id="password_confirm" name="reset[password_confirm]" pattern=".{8,}" title="Please confirm your password"/>
                </li>
            </ul>
            <div class="buttons-group">
                <input type="hidden" name="reset[password_nonce]" value="<?=BRequest::i()->get('n')?>"/>
                <button type="submit" name="do" value="reset">Reset Password</button>
            </div>
        </fieldset>
    </form>
</div>