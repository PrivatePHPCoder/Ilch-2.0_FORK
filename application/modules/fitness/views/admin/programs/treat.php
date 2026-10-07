<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\Difficulty;
use Modules\Fitness\Models\Program;

/** @var Program $program */
$program = $this->get('program');
/** @var \Modules\User\Models\Group[] $groups */
$groups = $this->get('groups');

$selectedDifficulty = (int)$this->originalInput('difficulty', $program->getDifficulty());
$selectedStatus = (int)$this->originalInput('status', $program->getStatus());
$accessType = (int)$this->originalInput('accessType', $program->getAccessType());
$selectedGroups = array_map('strval', (array)$this->originalInput(
    'groups',
    $program->isReadAccessAll() ? ['all'] : $program->getGroupIds()
));
$price = $program->isPaid() ? str_replace('.', ',', $program->getPrice()) : '';
?>
<h1><?=$this->getTrans($program->getId() ? 'edit' : 'add') ?></h1>
<form method="POST">
    <?=$this->getTokenField() ?>
    <div class="row mb-3<?=$this->validation()->hasError('title') ? ' has-error' : '' ?>">
        <label for="title" class="col-xl-2 col-form-label"><?=$this->getTrans('title') ?>:</label>
        <div class="col-xl-6">
            <input type="text" class="form-control" id="title" name="title" maxlength="255" value="<?=$this->escape($this->originalInput('title', $program->getTitle())) ?>" required>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('teaser') ? ' has-error' : '' ?>">
        <label for="teaser" class="col-xl-2 col-form-label"><?=$this->getTrans('teaser') ?>:</label>
        <div class="col-xl-6">
            <textarea class="form-control" id="teaser" name="teaser" rows="2" maxlength="500"><?=$this->escape($this->originalInput('teaser', $program->getTeaser())) ?></textarea>
            <div class="form-text"><?=$this->getTrans('teaserInfo') ?></div>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('goal') ? ' has-error' : '' ?>">
        <label for="goal" class="col-xl-2 col-form-label"><?=$this->getTrans('goal') ?>:</label>
        <div class="col-xl-6">
            <input type="text" class="form-control" id="goal" name="goal" maxlength="255" placeholder="<?=$this->getTrans('goalPlaceholder') ?>" value="<?=$this->escape($this->originalInput('goal', $program->getGoal())) ?>">
        </div>
    </div>
    <div class="row mb-3">
        <label for="ck_1" class="col-xl-2 col-form-label"><?=$this->getTrans('description') ?>:</label>
        <div class="col-xl-10">
            <textarea class="form-control ckeditor" id="ck_1" name="description" toolbar="ilch_html" rows="5"><?=$this->escape($this->originalInput('description', $program->getDescription())) ?></textarea>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('image') ? ' has-error' : '' ?>">
        <label for="selectedImage_1" class="col-xl-2 col-form-label"><?=$this->getTrans('image') ?>:</label>
        <div class="col-xl-6">
            <div class="input-group">
                <input type="text" class="form-control" id="selectedImage_1" name="image" maxlength="255" placeholder="<?=$this->getTrans('httpOrMedia') ?>" value="<?=$this->escape($this->originalInput('image', $program->getImage())) ?>">
                <span class="input-group-text"><a id="media" href="javascript:media_1()"><i class="fa-regular fa-image"></i></a></span>
            </div>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('difficulty') ? ' has-error' : '' ?>">
        <label for="difficulty" class="col-xl-2 col-form-label"><?=$this->getTrans('difficulty') ?>:</label>
        <div class="col-xl-4">
            <select class="form-select" id="difficulty" name="difficulty">
                <?php foreach (Difficulty::KEYS as $value => $translationKey) : ?>
                    <option value="<?=$value ?>"<?=$selectedDifficulty === $value ? ' selected' : '' ?>><?=$this->getTrans($translationKey) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('status') ? ' has-error' : '' ?>">
        <label for="status" class="col-xl-2 col-form-label"><?=$this->getTrans('status') ?>:</label>
        <div class="col-xl-4">
            <select class="form-select" id="status" name="status">
                <?php foreach (Program::STATUSES as $value => $translationKey) : ?>
                    <option value="<?=$value ?>"<?=$selectedStatus === $value ? ' selected' : '' ?>><?=$this->getTrans($translationKey) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-xl-6 form-text"><?=$this->getTrans('statusInfo') ?></div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('groups') ? ' has-error' : '' ?>">
        <label for="groups" class="col-xl-2 col-form-label"><?=$this->getTrans('visibleFor') ?>:</label>
        <div class="col-xl-4">
            <select class="choices-select form-control" id="groups" name="groups[]" multiple>
                <option value="all"<?=in_array('all', $selectedGroups, true) ? ' selected' : '' ?>><?=$this->getTrans('groupAll') ?></option>
                <?php foreach ($groups as $group) : ?>
                    <option value="<?=$group->getId() ?>"<?=in_array((string)$group->getId(), $selectedGroups, true) ? ' selected' : '' ?>><?=$this->escape($group->getName()) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('accessType') ? ' has-error' : '' ?>">
        <div class="col-xl-2 col-form-label"><?=$this->getTrans('accessType') ?>:</div>
        <div class="col-xl-10">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="accessType" id="accessFree" value="<?=Program::ACCESS_FREE ?>"<?=$accessType !== Program::ACCESS_PAID ? ' checked' : '' ?>>
                <label class="form-check-label" for="accessFree"><?=$this->getTrans('accessFree') ?></label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="accessType" id="accessPaid" value="<?=Program::ACCESS_PAID ?>"<?=$accessType === Program::ACCESS_PAID ? ' checked' : '' ?>>
                <label class="form-check-label" for="accessPaid"><?=$this->getTrans('accessPaid') ?></label>
            </div>
            <div class="form-text"><?=$this->getTrans('accessTypeInfo') ?></div>
        </div>
    </div>
    <div class="row mb-3" id="fxPriceRow">
        <label for="price" class="col-xl-2 col-form-label"><?=$this->getTrans('price') ?>:</label>
        <div class="col-xl-2<?=$this->validation()->hasError('price') ? ' has-error' : '' ?>">
            <input type="text" class="form-control" id="price" name="price" inputmode="decimal" placeholder="29,90" value="<?=$this->escape($this->originalInput('price', $price)) ?>">
        </div>
        <div class="col-xl-1<?=$this->validation()->hasError('currency') ? ' has-error' : '' ?>">
            <input type="text" class="form-control" id="currency" name="currency" maxlength="3" aria-label="<?=$this->getTrans('currency') ?>" value="<?=$this->escape($this->originalInput('currency', $program->getCurrency())) ?>">
        </div>
    </div>
    <?=$this->getSaveBar($program->getId() ? 'updateButton' : 'addButton') ?>
</form>

<?=$this->getDialog('mediaModal', $this->getTrans('media'), '<iframe frameborder="0"></iframe>') ?>
<script>
<?=$this->getMedia()
    ->addMediaButton($this->getUrl('admin/media/iframe/index/type/single/input/_1/'))
    ->addInputId('_1')
    ->addUploadController($this->getUrl('admin/media/index/upload'))
?>
    $(function () {
        new Choices('#groups', {
            ...choicesOptions,
            searchEnabled: true
        });

        function togglePrice() {
            $('#fxPriceRow').toggle($('#accessPaid').is(':checked'));
        }
        $('input[name="accessType"]').on('change', togglePrice);
        togglePrice();
    });
</script>
