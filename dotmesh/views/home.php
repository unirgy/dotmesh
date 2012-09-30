<?php if (DotMesh_Model_User::i()->isLoggedIn()): ?>
    <h2><?=$this->_('My Timeline')?></h2>
    <?=$this->view('newpost')?>
<?php else: ?>
    <h2><?=$this->_('Public Timeline')?></h2>
<?php endif ?>
<?=$this->view('timeline')?>