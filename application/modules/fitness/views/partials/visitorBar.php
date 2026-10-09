<?php

/** @var \Ilch\View $this */

/*
 * Notice for managers who see the fitness area like a visitor, with a button to switch back.
 */
?>
<?php if ($this->get('active')) : ?>
    <div class="fx-visitor-bar" role="status">
        <span><i class="fa-solid fa-eye"></i> <?=$this->getTrans('visitorViewActive') ?></span>
        <?php $this->load('partials/visitorSwitch.php', ['mode' => 'admin']); ?>
    </div>
<?php endif; ?>
