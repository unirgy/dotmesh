<?php $user = DotMesh_Model_User::i()->sessionUser(); ?>
<h2 class="block-title"><?=$this->_('My Subscriptions')?></h2>
<section class="section-users">
    <header><?=$this->_('Users')?></header>
    <form name="subscribe-remote-user" method="post" action="<?=BApp::href('u/')?>">
        <fieldset>
            <input type="text" name="user_uri" placeholder="dotmesh.org/username"/>
            <button type="submit" name="subscribe" value="1"><?=$this->_('Subscribe')?></button>
        </fieldset>
    </form>
    <ul>
<?php foreach ($user->subscribedToUsers() as $u): ?>
        <li>
            <form name="unsubscribe-user" method="post" action="<?=BApp::href('u/')?>" class="f-right">
                <fieldset>
                    <input type="hidden" name="user_uri" value="<?=$this->q($u->uri())?>"/>
                    <button type="submit" name="subscribe" value="0">X</button>
                </fieldset>
            </form>
            <a href="<?=$u->uri(true)?>">
                <img src="<?=$u->thumbUri()?>" class="avatar">
                <span class="node-name"><?=$u->node()->uri()?></span>
                <span class="user-name"><?=$u->username?></span>
            </a>
        </li>
<?php endforeach ?>
    </ul>
    <a href="#" class="link-view-all"><?=$this->_('View All')?></a>
</section>
<section class="section-tags">
    <header>^ <?=$this->_('Tags')?></header>
    <form name="subscribe-remote-user" method="post" action="<?=BApp::href('t/')?>">
        <fieldset>
            <input type="text" name="tag_uri" placeholder="dotmesh.org/tagname"/>
            <button type="submit" name="subscribe" value="1"><?=$this->_('Subscribe')?></button>
        </fieldset>
    </form>
    <ul>
<?php foreach ($user->subscribedToTags() as $t): ?>
        <li>
            <form name="unsubscribe-tag" method="post" action="<?=BApp::href('t/')?>" class="f-right">
                <fieldset>
                    <input type="hidden" name="tag_uri" value="<?=$this->q($t->uri())?>"/>
                    <button type="submit" name="subscribe" value="0">X</button>
                </fieldset>
            </form>
            <a href="<?=$t->uri(true)?>"><?=$t->tagname?></a>
        </li>
<?php endforeach ?>
    </ul>
    <a href="#" class="link-view-all"><?=$this->_('View All')?></a>
</section>
<h2 class="block-title"><?=$this->_('Subscribed To Me')?></h2>
<section class="section-users">
    <ul>
<?php foreach ($user->subscribers() as $u): ?>
        <li>
            <a href="<?=$u->uri(true)?>">
                <img src="<?=$u->thumbUri()?>" class="avatar">
                <span class="node-name"><?=$u->node()->uri()?></span>
                <span class="user-name"><?=$u->username?></span>
            </a>
        </li>
<?php endforeach ?>
    <a href="#" class="link-view-all"><?=$this->_('View All')?></a>
</section>