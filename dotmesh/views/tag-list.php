<?php defined('DOTMESH_ROOT_DIR') || die ?>

<?php if (!BRequest::i()->xhr()): ?>
<div class="site-main clearfix">
    <div class="page-main clearfix">
        <div class="col-main">
            <h2 class="timeline-block-title"><?=$this->q($this->title)?></h2>
<?php endif ?>

<?php if (!empty($this->list['rows'])): ?>
<ul class="timeline">
<?php foreach ($this->list['rows'] as $t): ?>
    <li id="timeline-<?=$t->id?>" class="timeline-item clearfix">
        <form name="unsubscribe-tag" method="post" action="<?=BApp::href('t/')?>" class="f-right">
            <fieldset>
                <input type="hidden" name="tag_uri" value="<?=$this->q($t->uri())?>"/>
                <button type="submit" name="subscribe" value="0">X</button>
            </fieldset>
        </form>
        <a href="<?=$t->uri(true)?>">
            <span class="node-name"><?=$t->node()->uri()?></span>
            <span class="user-name"><?=$t->tagnmame?></span>
        </a>
    </li>
<?php endforeach ?>
</ul>
<?php endif ?>

<?php if (!BRequest::i()->xhr()): ?>
<div class="timeline-loadmore" data-uri-pattern="<?=BUtil::setUrlQuery(BRequest::currentUrl(), array('p'=>'PAGE'))?>"
    <?=!empty($this->list['rows']) && !empty($this->list['is_last_page']) ? 'style="display:none"' : ''?> >
    <div class="loadmore"><?=$this->_('Load more ...')?></div>
    <div class="loader"><img src="<?=BApp::src('DotMesh', 'img/ajax-loader.gif')?>"/><?=$this->_('Please wait, loading ...')?></div>
</div>

        </div>
    </div>
</div>
<?php endif ?>