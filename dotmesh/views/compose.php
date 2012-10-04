<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php $user = DotMesh_Model_User::i()->sessionUser() ?>
<section class="new-post-block <?=!empty($user->preferences['default_private']) ? 'private-post' : '' ?> clearfix">
	<aside class="new-post-block-left">
		<dl>
			<dt>Subscribers</dt>
			<dd><?=$user->subscribersCnt()?></dd>
			<dt>Subscribed</dt>
			<dd><?=$user->subscribedToUsersCnt()?></dd>
			<dt>Posts</dt>
			<dd><?=$user->postsCnt()?></dd>
		</dl>
	</aside>
	<div class="new-post-block-right">
		<?php if (!$user): ?>
		<h3>Please login or sign up to post a new message</h3>
		<?php else: ?>
		<form name="post" method="post" action="<?=BApp::href('p/')?>">
		    <fieldset>
		<?php if ($this->post): ?>
		        <input type="hidden" name="inreplyto" value="<?=$this->q($this->post->postname)?>"/>
		<?php endif ?>
		        <a href="<?=$this->q($user->uri(true))?>" class="avatar">
		            <img src="<?=$user->thumbUri(50)?>" alt="<?=$this->q($user->fullname())?>"/>
		        </a>
		        <strong class="username"><?=$this->q($user->fullname())?></strong>
				<h3>Compose New Post</h3>
		        <div class="textarea">
		        	<textarea id="contents" name="contents" class="post-input" required><?=$this->q($this->contents)?></textarea>
		        </div>
		        <div class="buttons-group">
		        	<label for="is_private" class="private-post-label">
	                    <input type="checkbox" name="is_private" id="is_private"
	                        <?=!empty($user->preferences['default_private']) ? 'checked' : '' ?>/>
	                Private?</label>
		        	<?=$this->hook('newpost-flags')?>
		        	<button type="submit" class="button" name="do" value="new">Post</button>
		        </div>
		    </fieldset>
		</form>
		<?php endif ?>
	</div>
</section>