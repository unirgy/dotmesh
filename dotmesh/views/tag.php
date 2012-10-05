<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
    $tag = $this->tag;
    $isSubscribed = DotMesh_Model_User::i()->sessionUser()->isSubscribedToTag($tag);
?>
<div class="site-main timeline-col1-layout clearfix">
    <div class="user-profile-block">
		<div class="avatar">
	        <form method="post" name="user-actions" action="<?=$tag->uri(true)?>">
	            <fieldset>
	                <input type="hidden" name="tag_uri" value="<?=$tag->uri()?>"/>
	                <button type="submit" name="subscribe" value="<?=$isSubscribed ? 0 : 1 ?>" class="subscription-state state-<?=$isSubscribed?'subscribed':'subscribe' ?>">
	                    <span class="icon"></span><?=$isSubscribed ? 'Subscribed' : 'Subscribe' ?>
	                </button>
	            </fieldset>
	        </form>
		</div>
		<h1 class="user-url"><?=$tag->tagname?></h1>
		<table class="user-activity">
			<thead>
				<tr>
					<th>Subscribers</th>
					<th>Posts</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?=$tag->subscribersCnt()?></td>
					<td><?=$tag->postsCnt()?></td>
				</tr>
			</tbody>
		</table>
    </div>
	<?=$this->view('timeline')?>
</div>