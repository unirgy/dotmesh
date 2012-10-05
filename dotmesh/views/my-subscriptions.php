<?php $user = DotMesh_Model_User::i()->sessionUser(); ?>
<h2 class="block-title"><?=$this->_('My Subscriptions')?></h2>
<section class="section-users">
    <header><?=$this->_('Users')?></header>
    <ul>
<?php foreach ($user->subscribedToUsers() as $u): ?>
        <li>
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
    <ul>
<?php foreach ($user->subscribedToTags() as $t): ?>
        <li>
            <a href="<?=$t->uri(true)?>"><?=$t->tagname?></a>
        </li>
<?php endforeach ?>
    </ul>
    <a href="#" class="link-view-all"><?=$this->_('View All')?></a>
</section>