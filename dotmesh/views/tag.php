<?php defined('DOTMESH_ROOT_DIR') || die ?>
<h2><?=$this->_('^%s Timeline', $this->tag->tagname)?></h2>
<?=$this->view('compose')?>
<?=$this->view('timeline')?>