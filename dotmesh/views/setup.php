<h2><?=$this->_('Setup the node and admin user')?></h2>
<form name="setup" method="post" action="<?=BApp::href('a/setup')?>">
    <fieldset>
        <label for="nodeuri"><?=$this->_('This Node URI')?></label>
        <input type="text" required id="nodeuri" name="signup[nodeuri]" title="Format: yourserver.com/optional/path" placeholder="yourserver.com/path_to_dotmesh"/>
        <label for="username"><?=$this->_('Username')?></label>
        <input type="text" required id="username" name="signup[username]" pattern="^[a-zA-Z][a-zA-Z0-9_]+$" title="A valid user name, starting with letter, followed by letters, digits or underscore" placeholder="bond007 "/>
        <label for="username"><?=$this->_('Password')?></label>
        <input type="password" required id="password" name="signup[password]" pattern=".{8,}" title="Strong password, minimum 8 characters" placeholder="*****"/>
        <label for="username"><?=$this->_('Confirm')?></label>
        <input type="password" required id="password_comfirm" name="signup[password_confirm]" pattern=".{8,}" title="Please confirm your password" placeholder="*****"/>
        <label for="username"><?=$this->_('Email')?></label>
        <input type="email" required id="email" name="signup[email]" title="A valid email address" placeholder="bond@superduper.com"/>
        <label for="username"><?=$this->_('Name')?></label>
        <input type="text" required id="name" name="signup[name]" title="Your name" placeholder="James Bond"/>
        <button type="submit">Sign Up</button>
    </fieldset>
</form>