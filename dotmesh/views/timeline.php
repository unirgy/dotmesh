<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
$sessUser = DotMesh_Model_User::i()->sessionUser();
$userUri = $sessUser ? $this->q($sessUser->uri()) : null;
$isAdmin = $sessUser ? $sessUser->is_admin : false;
$localNode = DotMesh_Model_Node::i()->localNode();
$localNodeId = $localNode->id;
if (!$this->timeline) return;
$curPage = (int)BRequest::i()->get('p');
if (!$curPage) $curPage = 1;
$curSort = BRequest::i()->get('s');
$sortUri = $this->q(BUtil::setUrlQuery(BRequest::i()->currentUrl(), array('s'=>'SORT', 'status'=>null, 'message'=>null)));
$now = strtotime(BDb::now());
?>

<?php if (!BRequest::i()->xhr()): ?>
<div class="sort-by tiptip-title" title="<?=$this->_('Sort messages by')?>">
<?php foreach (explode(',', ',hot,best,worst,controversial') as $s): ?>
    <?=$s ? '<span class="pipe">|</span>' : ''?>
<?php if ($curSort==$s): ?>
    <strong><?=$s ? $s : 'recent'?></strong>
<?php else: ?>
    <a href="<?=str_replace('SORT', $s, $sortUri)?>"><?=$s ? $s : 'recent'?></a>
<?php endif // if ($curSort==$s): ?>
<?php endforeach // foreach (explode(',', ',hot,best,worst,controversial') as $s): ?>
</div>
<h2 class="timeline-block-title"><?=$this->q($this->title)?>
<?php if ($this->feed_uri): ?>
<a href="<?=BUtil::setUrlQuery($this->feed_uri, array('s'=>$curSort))?>" class="rss-link tiptip-title" title="<?=$this->_('RSS Feed for the current timeline')?>">
    <img src="<?=BApp::src('DotMesh', 'img/rss-icon.png')?>"/>
</a>
<?php endif // if ($this->feed_uri): ?>
</h2>
<div class="timeline-container">
<?php endif // if (!BRequest::i()->xhr()): ?>

<?php if (!empty($this->timeline['rows'])): ?>

<ul class="timeline" id="timeline-page-<?=$curPage?>">

<?php foreach ((array)$this->timeline['rows'] as $p): ?>

<?php $uri = $p->user()->uri(true); $name = $p->user()->fullname(); $isLocal = $p->node()->is_local; ?>
    <li id="timeline-<?=$p->id?>" class="timeline-item clearfix <?=$p->expanded?'expanded':''?> <?=$p->is_pinned?'pinned':''?> <?=$p->is_private?'private':''?>">
        <a name="<?=$this->q($p->postname)?>"></a>
        <form name="timeline-form-<?=$p->id?>" method="post" action="<?=$p->uri(true) ?>" data-local-uri="<?=BApp::href('p/_/api1.json')?>" data-is-local="<?=$isLocal?>">
<?php if (!$isLocal): ?>
            <input type="hidden" id="user_uri" name="user_uri" value="<?=$userUri?>"/>
            <input type="hidden" id="post_uri" name="post_uri" value="<?=$this->q($p->uri())?>"/>
            <input type="hidden" id="local_signature_ip" name="local_signature_ip"
                value="<?=$sessUser->generateRemoteSignature($localNode, true)?>"/>
            <input type="hidden" id="remote_signature_ip" name="remote_signature_ip"
                value="<?=$sessUser->generateRemoteSignature($p->node(), true)?>"/>
<?php endif ?>
            <a href="<?=$this->q($uri)?>" class="avatar"><img src="<?=$this->q($p->user()->thumbUri(50))?>" width="50" height="50" alt="<?=$this->q($uri)?>"/></a>
            <a href="<?=$p->uri(true)?>" class="tiptip-title posted-on" title="<?=date('r', strtotime($p->create_dt)) ?>"><?=BUtil::timeAgo($p->create_dt, $now) ?></a>
            <div class="actions-group actions-group-3">
                <?php if ($p->is_pinned): ?>
                    <?php if ($isAdmin): ?><button type="submit" name="pin" value="0"><?php endif ?>
                        <span class="icon icon-pinned-post tiptip-title" title="<?=$this->_('Pinned Post')?>"></span>
                    <?php if ($isAdmin): ?></button><?php endif ?>
                <?php endif ?>
                <?php if ($p->is_private): ?>
                    <span class="icon icon-private-post tiptip-title" title="<?=$this->_('Private Post')?>"></span>
                <?php endif ?>
            </div>
            <strong class="user-name"><?=$this->q($name)?></strong>
            <a href="<?=$this->q($uri)?>" class="user-url"><?=$this->q($p->user()->uri())?></a>
            <div class="content-wrapper">
                <div class="preview">
                    <?=$p->previewHtml()?>
                </div>
                <div class="contents">
                    <?=$p->contentsHtml()?>
                </div>
            </div>
<?php if ($p->echoUsers()): ?>
            <div class="echoed-by">
                <span class="icon tiptip-title" title="<?=$this->_("Echoed by users")?>"><?=$this->_('Echoed by:')?></span>
<?php foreach ($p->echoUsers() as $eu): ?>
                <a href="<?=$eu->uri(true)?>" class="tiptip-title" title="<?=$eu->uri()?>"><img src="<?=$eu->thumbUri()?>"/></a>
<?php endforeach ?>
            </div>
<?php endif ?>

<?php if ($p->contents && $p->preview!=$p->contents): ?>
            <a href="#" class="read-toggler preview-expand"><?=$this->_('Expand')?></a>
            <a href="#" class="read-toggler contents-collapse"><?=$this->_('Collapse')?></a>
<?php endif ?>

<?php //print_r($p->as_array()); ?>
			<div class="actions-group actions-group-1 always-visible">

<?php if (!$p->is_private && (!$sessUser || $p->user_id!==$sessUser->id)): ?>
                <button type="submit" name="echo" value="<?=$p->echo?0:1?>" class="tiptip-title button-echo<?=$p->echo?' active':''?>" <?=!$sessUser?'disabled':''?> title="<?=$this->_('Echo to your subscribers')?>"><span class="icon"></span><span class="label"><?=$this->_('Echo')?></span><span class="total-echo total <?=!$p->total_echos?'zero':''?>"><?=$p->total_echos?></span></button>
<?php endif ?>

                <button type="submit" name="star" value="<?=$p->star?0:1?>" class="tiptip-title button-star<?=$p->star?' active':''?>" <?=!$sessUser?'disabled':''?> title="<?=$this->_('Star Favorite')?>"><span class="icon"></span><span class="label"><?=$this->_('Star')?></span><span class="total-star total <?=!$p->total_stars?'zero':''?>"><?=$p->total_stars?></span></button>

<?php if (!$sessUser || $p->user_id!==$sessUser->id): ?>
                <button type="submit" name="flag" value="<?=$p->flag?0:1?>" class="tiptip-title button-flag<?=$p->flag?' active':''?>" <?=!$sessUser?'disabled':''?> title="<?=$this->_('Flag Offensive')?>"><span class="icon"></span><span class="label"><?=$this->_('Flag')?></span><span class="total-flag total <?=!$p->total_flags?'zero':''?>"><?=$p->total_flags?></span></button>
<?php endif ?>

                <button type="submit" name="vote_up" value="<?=$p->vote_up?0:1?>" class="tiptip-title button-vote_up<?=$p->vote_up==1?' active':''?>" <?=!$sessUser?'disabled':''?> title="<?=$this->_('Thumbs Up')?>"><span class="icon"></span><span class="label"><?=$this->_('Thumbs Up')?></span><span class="total-vote_up total <?=!$p->total_vote_up?'zero':''?>"><?=$p->total_vote_up?></span></button>

                <button type="submit" name="vote_down" value="<?=$p->vote_down?0:1?>" class="tiptip-title button-vote_down<?=$p->vote_down==1?' active':''?>" <?=!$sessUser?'disabled':''?> title="<?=$this->_('Thumbs Down')?>"><span class="icon"></span><span class="label"><?=$this->_('Thumbs Down')?></span><span class="total-vote_down total <?=!$p->total_vote_down?'zero':''?>"><?=$p->total_vote_down?></span></button>
            </div>

<?php if ($sessUser): ?>
            <div class="actions-group actions-group-2 hover-inline">
                <a href="<?=$p->uri(true)?>#reply" class="button-reply button"><span class="icon"></span><span><?=$this->_('Reply')?></span></a>

                <!--<a href="#" class="button-share button"><span class="icon"></span><span>Share</span></a>-->
<?php if ($isLocal && ($p->user_id==$sessUser->id || $isAdmin)): ?>
<!--
                <button type="submit" name="do" value="edit" class="button-edit button"><span class="icon"></span><span class="label">Edit</span></button>
-->
                <button type="submit" name="do" value="delete" onclick="return confirm('<?=$this->_('Are you sure?')?>')" class="button-delete button"><span class="icon"></span><span class="label"><?=$this->_('Delete')?></span></button>
<?php endif // if ($p->user_id==$sessUser->id || $isAdmin): ?>

			</div>
<?php endif // if ($sessUser): ?>
        </form>
    </li>
<?php endforeach // foreach ((array)$this->timeline['rows'] as $p): ?>

</ul>

<?php endif // if (!empty($this->timeline['rows'])): ?>

<?php if (!BRequest::i()->xhr()): ?>
</div>

<div class="timeline-loadmore" data-uri-pattern="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('p'=>'PAGE'))?>"
    <?=!empty($this->timeline['rows']) || !empty($this->timeline['is_last_page']) ? 'style="display:none"' : ''?> >
    <div class="loadmore"><?=$this->_('Load more ...')?></div>
    <div class="loader"><img src="<?=BApp::src('DotMesh', 'img/ajax-loader.gif')?>"/><?=$this->_('Please wait, loading ...')?></div>
</div>
<script>
$(function() { dotmeshMediaLinks('#timeline-page-<?=$curPage?>'); });
</script>

<?php elseif (!empty($this->timeline['rows'])): ?>

<script>
dotmeshMediaLinks('#timeline-page-<?=$curPage?>');
</script>

<?php endif // if (!BRequest::i()->xhr()): ?>
