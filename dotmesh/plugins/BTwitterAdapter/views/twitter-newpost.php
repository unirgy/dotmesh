<?php
$sess =& BSession::i()->data('twitter');
$screenName = !empty($sess['access_token']['screen_name']) ? $this->q($sess['access_token']['screen_name']) : '';
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
    <a class="twitter-signin-link" href="<?=BApp::href('twitter/redirect')?>" target="TwitterPopup" 
        title="<?=$this->_('Sign in to post on Twitter')?>">
        <img src="<?=BApp::src('BTwitterAdapter', 'lib/images/lighter.png')?>" alt="<?=$this->_('Sign in to post on Twitter')?>"/>
    </a>
    <div class="twitter-post-toggle">
        <label for="echo_twitter">Tweet as <span class="twitter-screenname"><?=$this->q($screenName)?></span></label>
        <input type="checkbox" name="echo_twitter" id="echo_twitter"/>
    </div>
</div>