<?php $user = $this->user ?>
<div class="site-main clearfix">
	<div class="user-profile-block">
		<img src="<?=$user->thumbUri(130)?>" alt="<?=$this->q($user->fullname())?>" class="avatar"/>
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
	<h2 class="timeline-block-title"><?=$this->_("%s's timeline", $this->user->fullname())?></h2>
	<?=$this->view('timeline')?>
</div>