<?php

/** @var \Ilch\View $this */
?>
<h1><?=$this->getTrans('menuFitness') ?></h1>
<p><?=$this->getTrans('overviewIntro') ?></p>

<div class="alert alert-info">
    <i class="fa-solid fa-circle-info"></i>
    <?=$this->getTrans('overviewNextSteps') ?>
</div>

<p>
    <i class="fa-solid fa-palette"></i>
    <?=$this->getTrans($this->get('ownLayout') ? 'overviewLayoutOn' : 'overviewLayoutOff') ?>
    <a href="<?=$this->getUrl(['controller' => 'settings', 'action' => 'index']) ?>"><?=$this->getTrans('menuSettings') ?></a>
</p>
