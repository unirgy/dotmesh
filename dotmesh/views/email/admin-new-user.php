<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php $u = $this->user; $n = DotMesh_Model_Node::i()->localNode() ?>
<!--{ To: "<?=$this->_('DotMesh Admin')?>" <<?=$n->support_email?>> }-->
<!--{ From: "DotMesh (<?=$n->uri()?>)" <<?=$n->support_email?>> }-->
<!--{ Subject: New User Sign Up }-->

Hello,

There was a new user signup: <?=$u->username?>

First name: <?=$u->firstname?>

Last name: <?=$u->lastname?>

Email: <?=$u->email?>

Twitter: <?=$u->twitter_screenname?>

