<?php defined('DOTMESH_ROOT_DIR') || die ?>
<div class="site-main clearfix">
	<div class="page-main clearfix">
		<aside class="col-left"><br/>
			<section class="section-trending">
			    <header><?=$this->_('Trending Tags')?></header>
			    <ul>
			        <li><a href="#">Trending tag</a></li>
			        <li><a href="#">Trending tag</a></li>
			        <li><a href="#">Trending tag</a></li>
			        <li><a href="#">Trending tag</a></li>
			        <li><a href="#">Trending tag</a></li>
			        <li><a href="#">Trending tag</a></li>
			        <li><a href="#">Trending tag</a></li>
			        <li><a href="#">Trending tag</a></li>
			        <li><a href="#">Trending tag</a></li>
			        <li><a href="#">Trending tag</a></li>
			    </ul>
			    <a href="#" class="link-view-all"><?=$this->_('View All')?></a>
			</section>
			<section class="section-trending">
			    <header><?=$this->_('Trending Users')?></header>
			    <ul>
			        <li><a href="#">Trending user</a></li>
			        <li><a href="#">Trending user</a></li>
			        <li><a href="#">Trending user</a></li>
			        <li><a href="#">Trending user</a></li>
			        <li><a href="#">Trending user</a></li>
			        <li><a href="#">Trending user</a></li>
			        <li><a href="#">Trending user</a></li>
			        <li><a href="#">Trending user</a></li>
			        <li><a href="#">Trending user</a></li>
			        <li><a href="#">Trending user</a></li>
			    </ul>
			    <a href="#" class="link-view-all"><?=$this->_('View All')?></a>
			</section>
		</aside>
		<div class="col-main">
	    	<?=$this->view('timeline')?>
	    </div>
	</div>
</div>