<div>
    <h1>DotMesh &trade;</h1>
    <?php if (DotMesh_Model_User::i()->isLoggedIn()): ?>
        <?=$this->_('Welcome, %s', DotMesh_Model_User::i()->sessionUser()->name)?>
        <a href="<?=BApp::href('a/logout')?>"><?=$this->_('Log Out')?></a>
    <?php else: ?>
        <form name="top_login" method="post" action="<?=BApp::href('a/login')?>">
            <fieldset>
                <input type="text" name="login[username]" required placeholder="username"/>
                <input type="password" name="login[password]" required placeholder="password"/>
                <button type="submit"><?=$this->_('Log In')?></button>
                <a href="<?=BApp::href('a/password_recover')?>"><?=$this->_('Password?')?></a>
                <a href="<?=BApp::href('a/signup')?>"><?=$this->_('Sign up')?></a>
            </fieldset>
        </form>
    <?php endif ?>
    
    <?php if (($status = BRequest::i()->get('status'))): ?>
        <ul class="messages">
            <li class="<?=$this->q($status)?>"><?=$this->_(BRequest::i()->get('message'))?></li>
        </ul>
    <?php endif ?>
</div>