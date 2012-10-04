<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php $user = DotMesh_Model_User::i()->sessionUser(); ?>
<div class="site-main clearfix">
    <?=$this->view('compose')?>
	<div class="page-main clearfix">
		<aside class="col-left">
            <?=$this->view('my-subscriptions')?>
		</aside>
		<div class="col-main">
		    <?=$this->view('timeline')?>
		</div>
	</div>
</div>