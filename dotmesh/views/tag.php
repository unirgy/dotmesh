<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
    $tag = $this->tag;
    $isSubscribed = DotMesh_Model_User::i()->sessionUser()->isSubscribedToTag($tag);
?>
<div class="site-main timeline-col1-layout clearfix">
    <div class="user-profile-block">
        <form method="post" name="tag-actions" action="<?=$tag->uri(true)?>">
            <fieldset>
                <input type="hidden" name="tag_uri" value="<?=$tag->uri()?>"/>
                <button type="submit" name="subscribe" value="<?=$isSubscribed ? 0 : 1 ?>">
                    <?=$isSubscribed ? 'Unsubscribe' : 'Subscribe' ?>
                </button>
            </fieldset>
        </form>
    </div>
	<?=$this->view('timeline')?>
</div>