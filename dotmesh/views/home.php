<div class="site-main clearfix">
	<?php $user = DotMesh_Model_User::i()->sessionUser(); ?>
		<?=$this->view('compose')?>
	
	<div class="page-main clearfix">
		<aside class="col-left">
			<h2 class="block-title">My Subscriptions</h2>
			<section class="section-users">
				<header>Users</header>
				<ul>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
					<li><a href="#"><img src="" class="avatar"><span class="node-name">dotmesh.org</span><span class="user-name">atlanteans</span></a></li>
				</ul>
				<a href="#" class="link-view-all">View All</a>
			</section>
			<section class="section-tags">
				<header>^ Tags</header>
				<ul>
					<li><a href="#">tag</a></li>
					<li><a href="#">tag</a></li>
					<li><a href="#">tag</a></li>
					<li><a href="#">tag</a></li>
					<li><a href="#">tag</a></li>
					<li><a href="#">tag</a></li>
					<li><a href="#">tag</a></li>
					<li><a href="#">tag</a></li>
					<li><a href="#">tag</a></li>
					<li><a href="#">tag</a></li>
				</ul>
				<a href="#" class="link-view-all">View All</a>
			</section>
		</aside>
		<div class="col-main">
		    <h2 class="timeline-block-title"><?=$this->_('My Timeline')?></h2>
		    <?=$this->view('timeline')?>
		</div>
	</div>
</div>