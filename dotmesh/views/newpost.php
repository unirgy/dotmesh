<?php $user = DotMesh_Model_User::i()->sessionUser() ?>
<?php if (!$user): ?>
<h3>Please login or sign up to post a new message</h3>
<?php else: ?>
<form name="post" method="post" action="<?=BApp::href('p/')?>">
    <fieldset>
<?php if ($this->post): ?>
        <input type="hidden" name="inreplyto" value="<?=$this->q($this->post->postname)?>"/>
<?php endif ?>
        <a href="<?=$this->q($user->uri(true))?>">
            <img src="<?=$user->thumbUri(50)?>" alt="<?=$this->q($user->fullname())?>" width="100" height="100"/>
        </a>
        <div><strong><?=$this->q($user->fullname())?></strong>
        <textarea id="contents" name="contents"></textarea>
        <label for="is_private">Private?</label><input type="checkbox" name="is_private" id="is_private"/>
        <label for="echo_twitter">Post on Twitter?</label><input type="checkbox" name="echo_twitter" id="echo_twitter"/>
        <button type="submit" class="button" name="do" value="new">Post</button>
    </fieldset>
</form>
<?php endif ?>