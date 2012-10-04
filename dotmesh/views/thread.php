<?php defined('DOTMESH_ROOT_DIR') || die ?>
<div class="site-main col1-layout clearfix">
	<a name="reply"></a>
	<?=$this->view('compose')?>
	<h2 class="timeline-block-title"><?=$this->_('Thread Timeline')?></h2>
	<?=$this->view('timeline')?>
</div>