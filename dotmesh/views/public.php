<?php defined('DOTMESH_ROOT_DIR') || die ?>
<div class="site-main clearfix">
	<div class="page-main clearfix">
		<aside class="col-left"><br/>
			<section class="section-tags">
			    <header><?=$this->_('Trending Tags')?></header>
			    <ul>
<?php foreach (DotMesh_Model_Tag::i()->trendingTags() as $t): ?>
			        <li><a href="<?=$t->uri(true)?>"><?=$this->q($t->tagname)?></a></li>
<?php endforeach ?>
			    </ul>
			    <a href="<?=BApp::href('t/')?>" class="link-view-all"><?=$this->_('View All')?></a>
			</section>
			<section class="section-users">
			    <header><?=$this->_('Trending Users')?></header>
			    <ul>
<?php foreach (DotMesh_Model_User::i()->trendingUsers() as $u): ?>
                    <li>
                        <a href="<?=$u->uri(true)?>">
                            <img src="<?=$u->thumbUri()?>" class="avatar">
                            <span class="node-name"><?=$u->node()->uri()?></span>
                            <span class="user-name"><?=$u->username?></span>
                        </a>
                    </li>
<?php endforeach ?>
			    </ul>
			    <a href="<?=BApp::href('u/')?>" class="link-view-all"><?=$this->_('View All')?></a>
			</section>
		</aside>
		<div class="col-main">
	    	<?=$this->view('timeline')?>
	    </div>
	</div>
</div>