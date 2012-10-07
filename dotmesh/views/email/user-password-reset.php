<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php $u = $this->user; $n = DotMesh_Model_Node::i()->localNode() ?>
<!--{ To: "<?=$u->firstname.' '.$u->lastname?>" <<?=$u->email?>> }-->
<!--{ From: "DotMesh (<?=$n->uri()?>)" <<?=$n->support_email?>> }-->
<!--{ Subject: Password reset confirmation }-->

Hello <?=$u->firstname?>,

Thank you, your password has been successfully reset.