<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
$sessUser = DotMesh_Model_User::i()->sessionUser();
$user = $this->user;
$isSubscribed = $sessUser && $sessUser->isSubscribedToUser($user);
?>
<div class="site-main timeline-col1-layout clearfix">
    <div class="user-profile-block clearfix">
        <div class="avatar">
            <img src="<?=$user->thumbUri(130)?>" alt="<?=$this->q($user->fullname())?>"/>
            <form method="post" name="user-actions" action="<?=$user->uri(true)?>">
                <fieldset>
                    <input type="hidden" name="user_uri" value="<?=$user->uri()?>"/>
    <?php if ($sessUser && $user->id!==$sessUser->id): ?>
                    <button type="submit" name="subscribe" value="<?=$isSubscribed ? 0 : 1 ?>" class="subscription-state state-<?=$isSubscribed?'subscribed':'subscribe' ?>" data-hover-label="<?=$this->_($isSubscribed ? 'Unsubscribe' : 'Subscribe') ?>">
                        <span class="icon"></span>
                        <span class="label"><?=$this->_($isSubscribed ? 'Subscribed' : 'Subscribe') ?></span>
                    </button>
    <?php endif ?>
                </fieldset>
            </form>
        </div>
        <h1 class="user-url"><?=$this->q($user->uri())?></h1>
        <table class="user-activity">
            <thead>
                <tr>
                    <th><?=$this->_('Subscribers')?></th>
                    <th><?=$this->_('Subscribed')?></th>
                    <th><?=$this->_('Posts')?></th>
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
            <p><?=DotMesh_Util::formatHtml($this->q($user->short_bio))?></p>
        </div>
    </div>
    <?=$this->view('timeline')?>
</div>