<?php

/** @var \Ilch\View $this */
?>
<h1><?=$this->getTrans('menuSettings') ?></h1>
<form method="POST">
    <?=$this->getTokenField() ?>
    <div class="row mb-3<?=$this->validation()->hasError('ownLayout') ? ' has-error' : '' ?>">
        <div class="col-xl-2 col-form-label">
            <?=$this->getTrans('ownLayout') ?>
        </div>
        <div class="col-xl-4">
            <div class="flipswitch">
                <input type="radio" class="flipswitch-input" id="ownLayout-on" name="ownLayout" value="1" <?=($this->originalInput('ownLayout', $this->get('ownLayout')) == '1') ? 'checked="checked"' : '' ?> />
                <label for="ownLayout-on" class="flipswitch-label flipswitch-label-on"><?=$this->getTrans('on') ?></label>
                <input type="radio" class="flipswitch-input" id="ownLayout-off" name="ownLayout" value="0" <?=($this->originalInput('ownLayout', $this->get('ownLayout')) != '1') ? 'checked="checked"' : '' ?> />
                <label for="ownLayout-off" class="flipswitch-label flipswitch-label-off"><?=$this->getTrans('off') ?></label>
                <span class="flipswitch-selection"></span>
            </div>
        </div>
        <div class="col-xl-6 form-text">
            <?=$this->getTrans('ownLayoutInfo') ?>
        </div>
    </div>

    <?=$this->getSaveBar() ?>
</form>
