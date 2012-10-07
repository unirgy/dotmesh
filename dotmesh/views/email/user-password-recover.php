<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php $u = $this->user; $n = DotMesh_Model_Node::i()->localNode() ?>
<!--{ To: "<?=$u->firstname.' '.$u->lastname?>" <<?=$u->email?>> }-->
<!--{ From: "DotMesh (<?=$n->uri()?>)" <<?=$n->support_email?>> }-->
<!--{ Subject: Password reset instructions }-->

Hello <?=$u->firstname?>,

Please go to this URL to reset your password:

<?=BApp::href('a/password_reset?u='.$u->username.'&n='.$u->password_nonce)?>