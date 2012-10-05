<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
$sessUser = DotMesh_Model_User::i()->sessionUser();
$user = $this->user;
$isSubscribed = $sessUser && $sessUser->isSubscribedToUser($user);
?>
<div class="site-main timeline-col1-layout clearfix">
	<div class="user-profile-block">
		<div class="avatar">
			<img src="<?=$user->thumbUri(130)?>" alt="<?=$this->q($user->fullname())?>"/>
	        <form method="post" name="user-actions" action="<?=$user->uri(true)?>">
	            <fieldset>
	                <input type="hidden" name="user_uri" value="<?=$user->uri()?>"/>
	<?php if ($sessUser && $user->id!==$sessUser->id): ?>
	                <button type="submit" name="subscribe" value="<?=$isSubscribed ? 0 : 1 ?>" class="subscription-state state-<?=$isSubscribed?'subscribed':'subscribe' ?>">
	                    <span class="icon"></span><?=$isSubscribed ? 'Subscribed' : 'Subscribe' ?>
	                </button>
	<?php endif ?>
	            </fieldset>
	        </form>
		</div>
		<h1 class="user-url"><?=$this->q($user->uri())?></h1>
		<table class="user-activity">
			<thead>
				<tr>
					<th>Subscribers</th>
					<th>Subscribed</th>
					<th>Posts</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?=$user->subscribersCnt()?></td>
					<td><?=$user->subscribedToUsersCnt()?></td>
					<td><?=$user->postsCnt()?></td>
				</tr>
			</tbody>
		</table>
		<div class="user-description">
			<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting</p>
		</div>
	</div>
	<?=$this->view('timeline')?>
</div>