<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Category $category */
$category = $this->get('category');
?>
<h1><?=$this->getTrans($category->getId() ? 'edit' : 'add') ?></h1>
<form method="POST">
    <?=$this->getTokenField() ?>
    <div class="row mb-3<?=$this->validation()->hasError('name') ? ' has-error' : '' ?>">
        <label for="name" class="col-xl-2 col-form-label">
            <?=$this->getTrans('name') ?>:
        </label>
        <div class="col-xl-6">
            <input type="text"
                   class="form-control"
                   id="name"
                   name="name"
                   maxlength="100"
                   value="<?=$this->escape($this->originalInput('name', $category->getName())) ?>"
                   required>
        </div>
    </div>
    <?=$this->getSaveBar($category->getId() ? 'updateButton' : 'addButton') ?>
</form>
