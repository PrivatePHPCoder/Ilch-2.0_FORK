<?php

/** @var \Ilch\View $this */

/*
 * Heading of an admin list with a button to add a new entry.
 * Expects the translation keys 'title' and 'addLabel'.
 */
?>
<h1><?=$this->getTrans($this->get('title')) ?></h1>
<p>
    <a class="btn btn-primary" href="<?=$this->getUrl(['action' => 'treat']) ?>">
        <i class="fa-solid fa-plus"></i> <?=$this->getTrans($this->get('addLabel')) ?>
    </a>
</p>
