<?php

/** @var \Ilch\View $this */

/*
 * Button for managers that switches the admin preview off ('mode' => 'visitor') or on again
 * ('mode' => 'admin') and leads back to the current page.
 */
$request = $this->getRequest();
?>
<form method="POST" action="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'preview', 'action' => 'index']) ?>" class="fx-visitor-switch">
    <?=$this->getTokenField() ?>
    <input type="hidden" name="mode" value="<?=$this->get('mode') === 'visitor' ? 'visitor' : 'admin' ?>">
    <input type="hidden" name="returnController" value="<?=$this->escape($request->getControllerName()) ?>">
    <input type="hidden" name="returnAction" value="<?=$this->escape($request->getActionName()) ?>">
    <input type="hidden" name="returnId" value="<?=(int)$request->getParam('id') ?>">
    <button type="submit" class="btn btn-sm <?=$this->get('mode') === 'visitor' ? 'btn-outline-primary' : 'btn-light' ?>">
        <i class="fa-solid <?=$this->get('mode') === 'visitor' ? 'fa-eye' : 'fa-user-shield' ?>"></i>
        <?=$this->getTrans($this->get('mode') === 'visitor' ? 'viewAsVisitor' : 'visitorViewEnd') ?>
    </button>
</form>
