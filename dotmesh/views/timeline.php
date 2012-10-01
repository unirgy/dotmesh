<?php
$userId = DotMesh_Model_User::i()->sessionUserId();
$localNodeId = DotMesh_Model_Node::i()->localNode()->id;
?>
<ul class="timeline">
<?php foreach ((array)$this->timeline as $p): $uri = $p->user()->uri(true); $name = $p->user()->fullname(); ?>
    <li id="timeline-<?=$p->id?>" class="timeline-item <?=$p->expanded?'expanded':''?>">
        <a name="<?=$this->q($p->postname)?>"></a>
        <form name="timeline-form-<?=$p->id?>" method="post" action="<?=$p->uri(true)?>">
            <a href="<?=$this->q($uri)?>"><img src="<?=$p->user()->thumbUri(50)?>" alt="<?=$this->q($name)?>" width="50" height="50"/></a>
            <div> <strong><?=$this->q($name)?></strong>
            <a href="<?=$this->q($uri)?>"><?=$this->q($p->user()->uri())?></a></div>
            <div class="content-wrapper">
                <div class="preview">
                    <?=$p->previewHtml()?>
<?php if ($p->preview!=$p->contents): ?>
                    <a href="#" class="preview-expand"><?=$this->_('More...')?></a>
<?php endif ?>
                </div>
                <div class="contents">
                    <?=$p->contentsHtml()?>
<?php if ($p->preview!=$p->contents): ?>
                    <a href="#" class="contents-collapse"><?=$this->_('Less...')?></a>
<?php endif ?>
                </div>
            </div>
            <a href="<?=$p->uri(true)?>"><?=BUtil::timeAgo($p->create_dt)/*$this->_('%s ago', BUtil::timeAgo($p->create_dt, null, true))*/?></a>
            <div class="actions hover-inline">
<?php if ($p->node_id==$localNodeId): ?>
                <a href="<?=$p->uri(true)?>#reply">Reply</a>
<?php endif ?>
                <button type="submit" name="do" value="<?=$p->star?'un-':''?>star" <?=$p->star?'style="background:green"':''?>>Star</button>
                <button type="submit" name="do" value="<?=$p->report?'un-':''?>report" <?=$p->report?'style="background:red"':''?>>Report</button>
                <button type="submit" name="do" value="<?=$p->user_score==1?'un-':''?>score-up" <?=$p->user_score==1?'style="background:green"':''?>>Thumbs Up</button>
                <button type="submit" name="do" value="<?=$p->user_score==-1?'un-':''?>score-down" <?=$p->user_score==-1?'style="background:red"':''?>>Thumbs Down</button>
<?php if ($p->user_id==$userId): ?>
                <button type="submit" name="do" value="delete" onclick="return confirm('Are you sure?')">Delete</button>
                <button type="submit" name="do" value="edit">Edit</button>
<?php endif ?>
            </div>
        </form>
        <hr/>
    </li>
<?php endforeach ?>
</ul>