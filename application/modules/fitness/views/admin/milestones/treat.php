<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\Milestone;

/** @var Milestone $milestone */
$milestone = $this->get('milestone');
/** @var array<int, \Modules\Fitness\Models\Program> $programs */
$programs = $this->get('programs');

$type = (string)$this->originalInput('type', $milestone->getType());
$programId = (int)$this->originalInput('programId', (int)$milestone->getProgramId());
$icon = (string)$this->originalInput('icon', $milestone->getIcon());
$isActive = (int)$this->originalInput('active', (int)$milestone->isActive());
?>
<h1><?=$this->getTrans($milestone->getId() ? 'edit' : 'add') ?></h1>
<form method="POST">
    <?=$this->getTokenField() ?>
    <div class="row mb-3<?=$this->validation()->hasError('type') ? ' has-error' : '' ?>">
        <label for="type" class="col-xl-2 col-form-label">
            <?=$this->getTrans('milestoneType') ?>:
        </label>
        <div class="col-xl-4">
            <select class="form-select" id="type" name="type">
                <?php foreach (Milestone::TYPES as $typeKey => $typeName) : ?>
                    <option value="<?=$typeKey ?>" data-max="<?=Milestone::MAX_THRESHOLDS[$typeKey] ?>"<?=$type === $typeKey ? ' selected' : '' ?>><?=$this->getTrans($typeName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('threshold') ? ' has-error' : '' ?>">
        <label for="threshold" class="col-xl-2 col-form-label">
            <?=$this->getTrans('milestoneThreshold') ?>:
        </label>
        <div class="col-xl-2">
            <input type="number"
                   class="form-control"
                   id="threshold"
                   name="threshold"
                   min="1"
                   max="<?=Milestone::MAX_THRESHOLDS[$type] ?? 1 ?>"
                   value="<?=$this->escape($this->originalInput('threshold', $milestone->getThreshold())) ?>"
                   required>
        </div>
        <div class="col-xl-6 form-text"><?=$this->getTrans('milestoneThresholdInfo') ?></div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('programId') ? ' has-error' : '' ?>">
        <label for="programId" class="col-xl-2 col-form-label">
            <?=$this->getTrans('milestoneScope') ?>:
        </label>
        <div class="col-xl-4">
            <select class="form-select" id="programId" name="programId">
                <option value="0"><?=$this->getTrans('milestoneAllPrograms') ?></option>
                <?php foreach ($programs as $program) : ?>
                    <option value="<?=$program->getId() ?>"<?=$programId === $program->getId() ? ' selected' : '' ?>><?=$this->escape($program->getTitle()) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-xl-6 form-text"><?=$this->getTrans('milestoneScopeInfo') ?></div>
    </div>
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
                   placeholder="<?=$this->getTrans('milestoneTitlePlaceholder') ?>"
                   value="<?=$this->escape($this->originalInput('title', $milestone->getTitle())) ?>">
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('description') ? ' has-error' : '' ?>">
        <label for="description" class="col-xl-2 col-form-label">
            <?=$this->getTrans('description') ?>:
        </label>
        <div class="col-xl-6">
            <textarea class="form-control" id="description" name="description" rows="2" maxlength="1000"><?=$this->escape($this->originalInput('description', $milestone->getDescription())) ?></textarea>
            <div class="form-text"><?=$this->getTrans('milestoneDescriptionInfo') ?></div>
        </div>
    </div>
    <div class="row mb-3<?=$this->validation()->hasError('icon') ? ' has-error' : '' ?>">
        <div class="col-xl-2 col-form-label">
            <?=$this->getTrans('milestoneIcon') ?>:
        </div>
        <div class="col-xl-6">
            <?php foreach (Milestone::ICONS as $index => $iconClass) : ?>
                <input type="radio" class="btn-check" name="icon" id="icon<?=$index ?>" value="<?=$iconClass ?>" autocomplete="off"<?=$icon === $iconClass ? ' checked' : '' ?>>
                <label class="btn btn-outline-warning mb-1" for="icon<?=$index ?>" title="<?=$iconClass ?>"><i class="<?=$iconClass ?> fa-fw"></i></label>
            <?php endforeach; ?>
        </div>
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
        <div class="col-xl-6 form-text"><?=$this->getTrans('milestoneActiveInfo') ?></div>
    </div>
    <?=$this->getSaveBar($milestone->getId() ? 'updateButton' : 'addButton') ?>
</form>
<script>
    $(function () {
        // Keep the allowed maximum of the threshold in line with the type.
        $('#type').on('change', function () {
            $('#threshold').attr('max', $(this).find(':selected').data('max'));
        });
    });
</script>
