<?php defined('DOTMESH_ROOT_DIR') || die ?>
<div class="site-main timeline-col1-layout clearfix">
	<a name="reply"></a>
	<?=$this->view('compose')?>
	<?=$this->view('timeline')?>
</div>
<script>
$(function() {
    $('#contents').focus().setSelectionRange('end');
})
</script>