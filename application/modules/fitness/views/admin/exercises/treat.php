<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\Difficulty;

/** @var \Modules\Fitness\Models\Exercise $exercise */
$exercise = $this->get('exercise');
/** @var \Modules\Fitness\Models\Category[] $categories */
$categories = $this->get('categories');
/** @var \Modules\Fitness\Models\MuscleGroup[] $muscleGroups */
$muscleGroups = $this->get('muscleGroups');

$selectedCategory = (int)$this->originalInput('categoryId', $exercise->getCategoryId());
$selectedDifficulty = (int)$this->originalInput('difficulty', $exercise->getDifficulty());
$selectedPrimary = (int)$this->originalInput('primaryMuscleGroup', $exercise->getPrimaryMuscleGroupId());
$selectedMuscleGroups = array_map('intval', (array)$this->originalInput('muscleGroups', $exercise->getMuscleGroupIds()));
$isPublic = (int)$this->originalInput('isPublic', (int)$exercise->isPublic());
$isActive = (int)$this->originalInput('active', (int)$exercise->isActive());
?>
<h1><?=$this->getTrans($exercise->getId() ? 'edit' : 'add') ?></h1>
<form method="POST">
    <?=$this->getTokenField() ?>
    <div class="row mb-3<?=$this->validation()->hasError('title') ? ' has-error' : '' ?>">
        <label for="title" class="col-xl-2 col-form-label">
            <?=$this->getTrans('title') ?>:
        </label>
        <div class="col-xl-6">
            <input type="text"
                   class="form-control"
                   id="title"
                   name="title"
                   maxlength="255"
                   value="<?=$this->escape($this->originalInput('title', $exercise->getTitle())) ?>"
                   required>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('categoryId') ? ' has-error' : '' ?>">
        <label for="categoryId" class="col-xl-2 col-form-label">
            <?=$this->getTrans('categoryId') ?>:
        </label>
        <div class="col-xl-4">
            <select class="form-select" id="categoryId" name="categoryId">
                <option value="0"><?=$this->getTrans('noSelection') ?></option>
                <?php foreach ($categories as $category) : ?>
                    <option value="<?=$category->getId() ?>"<?=$selectedCategory === $category->getId() ? ' selected' : '' ?>><?=$this->escape($category->getName()) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('difficulty') ? ' has-error' : '' ?>">
        <label for="difficulty" class="col-xl-2 col-form-label">
            <?=$this->getTrans('difficulty') ?>:
        </label>
        <div class="col-xl-4">
            <select class="form-select" id="difficulty" name="difficulty">
                <?php foreach (Difficulty::KEYS as $value => $translationKey) : ?>
                    <option value="<?=$value ?>"<?=$selectedDifficulty === $value ? ' selected' : '' ?>><?=$this->getTrans($translationKey) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('primaryMuscleGroup') ? ' has-error' : '' ?>">
        <label for="primaryMuscleGroup" class="col-xl-2 col-form-label">
            <?=$this->getTrans('primaryMuscleGroup') ?>:
        </label>
        <div class="col-xl-4">
            <select class="form-select" id="primaryMuscleGroup" name="primaryMuscleGroup">
                <option value="0"><?=$this->getTrans('noSelection') ?></option>
                <?php foreach ($muscleGroups as $muscleGroup) : ?>
                    <option value="<?=$muscleGroup->getId() ?>"<?=$selectedPrimary === $muscleGroup->getId() ? ' selected' : '' ?>><?=$this->escape($muscleGroup->getName()) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-xl-2 col-form-label">
            <?=$this->getTrans('muscleGroups') ?>:
        </div>
        <div class="col-xl-10">
            <?php if ($muscleGroups) : ?>
                <?php foreach ($muscleGroups as $muscleGroup) : ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="checkbox"
                               id="muscleGroup<?=$muscleGroup->getId() ?>"
                               name="muscleGroups[]"
                               value="<?=$muscleGroup->getId() ?>"<?=in_array($muscleGroup->getId(), $selectedMuscleGroups, true) && $muscleGroup->getId() !== $selectedPrimary ? ' checked' : '' ?>>
                        <label class="form-check-label" for="muscleGroup<?=$muscleGroup->getId() ?>"><?=$this->escape($muscleGroup->getName()) ?></label>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="form-text">
                    <?=$this->getTrans('noMuscleGroups') ?>
                    <a href="<?=$this->getUrl(['controller' => 'musclegroups', 'action' => 'treat']) ?>"><?=$this->getTrans('add') ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <div class="row mb-3">
        <label for="ck_1" class="col-xl-2 col-form-label">
            <?=$this->getTrans('description') ?>:
        </label>
        <div class="col-xl-10">
            <textarea class="form-control ckeditor"
                      id="ck_1"
                      name="description"
                      toolbar="ilch_html"
                      rows="5"><?=$this->escape($this->originalInput('description', $exercise->getDescription())) ?></textarea>
        </div>
    </div>
    <div class="row mb-3">
        <label for="ck_2" class="col-xl-2 col-form-label">
            <?=$this->getTrans('instructions') ?>:
        </label>
        <div class="col-xl-10">
            <textarea class="form-control ckeditor"
                      id="ck_2"
                      name="instructions"
                      toolbar="ilch_html"
                      rows="5"><?=$this->escape($this->originalInput('instructions', $exercise->getInstructions())) ?></textarea>
        </div>
    </div>
    <div class="row mb-3">
        <label for="notes" class="col-xl-2 col-form-label">
            <?=$this->getTrans('notes') ?>:
        </label>
        <div class="col-xl-6">
            <textarea class="form-control" id="notes" name="notes" rows="3"><?=$this->escape($this->originalInput('notes', $exercise->getNotes())) ?></textarea>
            <div class="form-text"><?=$this->getTrans('notesInfo') ?></div>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('image') ? ' has-error' : '' ?>">
        <label for="selectedImage_1" class="col-xl-2 col-form-label">
            <?=$this->getTrans('image') ?>:
        </label>
        <div class="col-xl-6">
            <div class="input-group">
                <input type="text"
                       class="form-control"
                       id="selectedImage_1"
                       name="image"
                       maxlength="255"
                       placeholder="<?=$this->getTrans('httpOrMedia') ?>"
                       value="<?=$this->escape($this->originalInput('image', $exercise->getImage())) ?>">
                <span class="input-group-text"><a id="media" href="javascript:media_1()"><i class="fa-regular fa-image"></i></a></span>
            </div>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('videoUrl') ? ' has-error' : '' ?>">
        <label for="videoUrl" class="col-xl-2 col-form-label">
            <?=$this->getTrans('videoUrl') ?>:
        </label>
        <div class="col-xl-6">
            <input type="url"
                   class="form-control"
                   id="videoUrl"
                   name="videoUrl"
                   maxlength="255"
                   placeholder="https://www.youtube.com/watch?v=…"
                   value="<?=$this->escape($this->originalInput('videoUrl', $exercise->getVideoUrl())) ?>">
            <div class="form-text"><?=$this->getTrans('videoUrlInfo') ?></div>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('isPublic') ? ' has-error' : '' ?>">
        <div class="col-xl-2 col-form-label">
            <?=$this->getTrans('isPublic') ?>:
        </div>
        <div class="col-xl-4">
            <div class="flipswitch">
                <input type="radio" class="flipswitch-input" id="isPublic-on" name="isPublic" value="1"<?=$isPublic === 1 ? ' checked' : '' ?>>
                <label for="isPublic-on" class="flipswitch-label flipswitch-label-on"><?=$this->getTrans('on') ?></label>
                <input type="radio" class="flipswitch-input" id="isPublic-off" name="isPublic" value="0"<?=$isPublic !== 1 ? ' checked' : '' ?>>
                <label for="isPublic-off" class="flipswitch-label flipswitch-label-off"><?=$this->getTrans('off') ?></label>
                <span class="flipswitch-selection"></span>
            </div>
        </div>
        <div class="col-xl-6 form-text"><?=$this->getTrans('isPublicInfo') ?></div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('active') ? ' has-error' : '' ?>">
        <div class="col-xl-2 col-form-label">
            <?=$this->getTrans('active') ?>:
        </div>
        <div class="col-xl-4">
            <div class="flipswitch">
                <input type="radio" class="flipswitch-input" id="active-on" name="active" value="1"<?=$isActive === 1 ? ' checked' : '' ?>>
                <label for="active-on" class="flipswitch-label flipswitch-label-on"><?=$this->getTrans('on') ?></label>
                <input type="radio" class="flipswitch-input" id="active-off" name="active" value="0"<?=$isActive !== 1 ? ' checked' : '' ?>>
                <label for="active-off" class="flipswitch-label flipswitch-label-off"><?=$this->getTrans('off') ?></label>
                <span class="flipswitch-selection"></span>
            </div>
        </div>
        <div class="col-xl-6 form-text"><?=$this->getTrans('activeInfo') ?></div>
    </div>
    <?=$this->getSaveBar($exercise->getId() ? 'updateButton' : 'addButton') ?>
</form>

<?=$this->getDialog('mediaModal', $this->getTrans('media'), '<iframe frameborder="0"></iframe>') ?>
<script>
<?=$this->getMedia()
    ->addMediaButton($this->getUrl('admin/media/iframe/index/type/single/input/_1/'))
    ->addInputId('_1')
    ->addUploadController($this->getUrl('admin/media/index/upload'))
?>
</script>
