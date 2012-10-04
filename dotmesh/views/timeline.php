<?php defined('DOTMESH_ROOT_DIR') || die ?>
<?php
$userId = DotMesh_Model_User::i()->sessionUserId();
$localNodeId = DotMesh_Model_Node::i()->localNode()->id;
if (!$this->timeline) return;
$curSort = BRequest::i()->get('s');
$sortUri = $this->q(BUtil::setUrlQuery(BRequest::i()->currentUrl(), array('s'=>'SORT')));
?>

<?php if (!BRequest::i()->xhr()): ?>
        <div class="f-right" style="margin:10px 10px; font-size:12px;">
<?php foreach (explode(',', ',hot,best,worst,controversial') as $s): ?>
            <?=$s ? ' | ' : ''?>
<?php if ($curSort==$s): ?>
            <strong><?=$s ? $s : 'recent'?></strong>
<?php else: ?>
            <a href="<?=str_replace('SORT', $s, $sortUri)?>"><?=$s ? $s : 'recent'?></a>
<?php endif ?>
<?php endforeach ?>
        </div>
    <h2 class="timeline-block-title"><?=$this->q($this->title)?>
<?php if ($this->feed_uri): ?>
        <a href="<?=$this->feed_uri?>" class="rss-link"><img src="<?=BApp::src('DotMesh', 'img/rss-icon.png')?>"/></a>
<?php endif ?>
    </h2>
<?php endif ?>

<ul class="timeline">
<?php foreach ((array)$this->timeline as $p): $uri = $p->user()->uri(true); $name = $p->user()->fullname(); ?>
    <li id="timeline-<?=$p->id?>" class="timeline-item clearfix <?=$p->expanded?'expanded':''?> <?=$p->is_private?'private':''?>">
        <a name="<?=$this->q($p->postname)?>"></a>
        <form name="timeline-form-<?=$p->id?>" method="post" action="<?=$p->uri(true)?>">
            <a href="<?=$this->q($uri)?>" class="avatar"><img src="<?=$p->user()->thumbUri(50)?>" width="50" height="50"/></a>
            <a href="<?=$p->uri(true)?>" class="tiptip-title posted-on" title="<?=date('r', strtotime($p->create_dt)) ?>"><?=BUtil::timeAgo($p->create_dt) ?></a>
            <?php if ($p->is_private): ?>
                <span class="icon icon-private-post tiptip-title" title="<?=$this->_('Private Post')?>"></span>
            <?php endif ?>
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
<?php if ($p->preview!=$p->contents): ?>
                <a href="#" class="read-toggler preview-expand"><?=$this->_('Expand')?></a>
                <a href="#" class="read-toggler contents-collapse"><?=$this->_('Collapse')?></a>
<?php endif ?>
<?php //print_r($p->as_array()); ?>
			<div class="actions-group actions-group-1 always-visible">
<?php if (!$p->is_private): ?>
                <button type="submit" name="echo" value="<?=$p->echo?0:1?>" class="tiptip-title button-echo<?=$p->echo?' active':''?>" <?=!$userId?'disabled':''?> title="<?=$this->_('Echo')?>"><span class="icon"></span><span class="label"><?=$this->_('Echo')?></span><span class="total-echo total <?=!$p->total_echos?'zero':''?>"><?=$p->total_echos?></span></button>
<?php endif ?>
                <button type="submit" name="star" value="<?=$p->star?0:1?>" class="tiptip-title button-star<?=$p->star?' active':''?>" <?=!$userId?'disabled':''?> title="<?=$this->_('Star')?>"><span class="icon"></span><span class="label">Star</span><span class="total-star total <?=!$p->total_stars?'zero':''?>"><?=$p->total_stars?></span></button>
                <button type="submit" name="flag" value="<?=$p->flag?0:1?>" class="tiptip-title button-flag<?=$p->flag?' active':''?>" <?=!$userId?'disabled':''?> title="<?=$this->_('Flag')?>"><span class="icon"></span><span class="label">Flag</span><span class="total-flag total <?=!$p->total_flags?'zero':''?>"><?=$p->total_flags?></span></button>
                <button type="submit" name="vote_up" value="<?=$p->vote_up?0:1?>" class="tiptip-title button-vote_up<?=$p->vote_up==1?' active':''?>" <?=!$userId?'disabled':''?> title="<?=$this->_('Thumbs Up')?>"><span class="icon"></span><span class="label">Thumbs Up</span><span class="total-vote_up total <?=!$p->total_vote_up?'zero':''?>"><?=$p->total_vote_up?></span></button>
                <button type="submit" name="vote_down" value="<?=$p->vote_down?0:1?>" class="tiptip-title button-vote_down<?=$p->vote_down==1?' active':''?>" <?=!$userId?'disabled':''?> title="<?=$this->_('Thumbs Down')?>"><span class="icon"></span><span class="label">Thumbs Down</span><span class="total-vote_down total <?=!$p->total_vote_down?'zero':''?>"><?=$p->total_vote_down?></span></button>
            </div>

<?php if ($userId): ?>
            <div class="actions-group actions-group-2 hover-inline">
                <a href="<?=$p->uri(true)?>#reply" class="button-reply button"><span class="icon"></span><span>Reply</span></a>
                <!--<a href="#" class="button-share button"><span class="icon"></span><span>Share</span></a>-->
<?php if ($p->user_id==$userId): ?>
                <button type="submit" name="do" value="edit" class="button-edit button"><span class="icon"></span><span class="label">Edit</span></button>
                <button type="submit" name="do" value="delete" onclick="return confirm('Are you sure?')" class="button-delete button"><span class="icon"></span><span class="label">Delete</span></button>
<?php endif ?>
			</div>
<?php endif ?>
        </form>
    </li>
<?php endforeach ?>
</ul>

<?php if (!BRequest::i()->xhr()): ?>
<div class="timeline-loadmore" data-uri-pattern="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('p'=>'PAGE'))?>">
    <div class="loadmore"><?=$this->_('Load more ...')?></div>
    <div class="loader"><img src="<?=BApp::src('DotMesh', 'img/ajax-loader.gif')?>"/><?=$this->_('Please wait, loading ...')?></div>
</div>
<?php endif ?>