<?php defined('DOTMESH_ROOT_DIR') || die ?>
<div class="page-404">
	<h2><?=$this->_("This is not the page you're looking for")?></h2>
	<p><?=$this->_('Why don\'t you <a href="javascript:history.back()">step back</a>, or start from the <a href="%s" title="Home page">Home page</a>?', array(BApp::href()))?></p>
</div>