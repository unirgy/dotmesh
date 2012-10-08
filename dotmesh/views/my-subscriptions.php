<?php $user = DotMesh_Model_User::i()->sessionUser(); ?>
<h2 class="block-title"><?=$this->_('My Subscriptions')?></h2>
<section class="section-users">
    <header><?=$this->_('Users')?></header>
    <form name="subscribe-remote-user" method="post" action="<?=BApp::href('u/')?>" class="subscribe-remote-user">
        <fieldset>
            <input type="text" name="user_uri" placeholder="dotmesh.org/username"/>
            <button type="submit" class="icon" name="subscribe" value="1"><?=$this->_('Subscribe')?></button>
        </fieldset>
    </form>
    <ul>
<?php foreach ($user->subscribedToUsers() as $u): ?>
        <li>
            <a href="<?=$u->uri(true)?>">
            <form name="unsubscribe-user" class="unsubscribe f-right" method="post" action="<?=BApp::href('u/')?>">
                <fieldset>
                    <input type="hidden" name="user_uri" value="<?=$this->q($u->uri())?>"/>
                    <button type="submit" class="icon" name="subscribe" value="0">X</button>
                </fieldset>
            </form>
                <img src="<?=$u->thumbUri()?>" class="avatar">
                <span class="node-name"><?=$u->node()->uri()?></span>
                <span class="user-name"><?=$u->username?></span>
            </a>
        </li>
<?php endforeach ?>
    </ul>
    <a href="<?=BApp::href('a/pub_users')?>" class="link-view-all"><?=$this->_('View All')?></a>
</section>
<section class="section-tags">
    <header>^ <?=$this->_('Tags')?></header>
    <form name="subscribe-remote-user" method="post" action="<?=BApp::href('t/')?>" class="subscribe-remote-user">
        <fieldset>
            <input type="text" name="tag_uri" placeholder="dotmesh.org/tagname"/>
            <button type="submit" class="icon" name="subscribe" value="1"><?=$this->_('Subscribe')?></button>
        </fieldset>
    </form>
    <ul>
<?php foreach ($user->subscribedToTags() as $t): ?>
        <li>
            <a href="<?=$t->uri(true)?>">
            <form name="unsubscribe-tag" class="unsubscribe f-right" method="post" action="<?=BApp::href('t/')?>">
                <fieldset>
                    <input type="hidden" name="tag_uri" value="<?=$this->q($t->uri())?>"/>
                    <button type="submit" class="icon" name="subscribe" value="0">X</button>
                </fieldset>
            </form>
            <?=$t->tagname?></a>
        </li>
<?php endforeach ?>
    </ul>
    <a href="<?=BApp::href('a/pub_tags')?>" class="link-view-all"><?=$this->_('View All')?></a>
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
	</ul>
    <a href="<?=BApp::href('a/sub_users')?>" class="link-view-all"><?=$this->_('View All')?></a>
</section>