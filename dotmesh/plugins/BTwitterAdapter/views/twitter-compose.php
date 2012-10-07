<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
if (!BTwitterAdapter::i()->isConfigured()) return;
$user = DotMesh_Model_User::i()->sessionUser();
$screenName = $user->twitter_screenname;
?>

<style>
.twitter-post-toggle { display:none; }
.signedin .twitter-signin-link { display:none }
.signedin .twitter-post-toggle { display:block; }
</style>

<script>
function toggleTwitterPost(screenName) {
    $('.twitter-screenname').html(screenName);
    $('.post-to-twitter').addClass('signedin');
}
$(function() {
    $('.twitter-signin-link').on('click', function(event) {
        window.open(this.href, this.target, true);
    });
})
</script>

<div class="post-to-twitter <?=!empty($screenName) ? 'signedin' : ''?>">
    <a class="twitter-signin-link" href="<?=BApp::href('a/twitter/redirect').'?to=post'?>" target="TwitterPopup"
        title="<?=$this->_('Sign in to post on Twitter')?>">
        <img src="<?=BApp::src('BTwitterAdapter', 'lib/images/lighter.png')?>" alt="<?=$this->_('Sign in to post on Twitter')?>"/>
    </a>
    <div class="twitter-post-toggle">
        <label for="is_tweeted"><input type="checkbox" name="is_tweeted" id="is_tweeted"/> Tweet as <span class="twitter-screenname"><?=$this->q($screenName)?></span></label>
    </div>
</div>