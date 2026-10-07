<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\Difficulty;

/** @var \Modules\Fitness\Models\Workout $workout */
$workout = $this->get('workout');
/** @var array<int, \Modules\Fitness\Models\Exercise> $exercises */
$exercises = $this->get('exercises');

$selectedDifficulty = (int)$this->originalInput('difficulty', $workout->getDifficulty());
$isActive = (int)$this->originalInput('active', (int)$workout->isActive());

// Rows of the exercise table: after a failed validation from the old input, otherwise from the workout.
$rows = [];
$oldItems = $this->originalInput('items', null);
if (is_array($oldItems)) {
    foreach ($oldItems as $key => $item) {
        $rows[preg_replace('/[^A-Za-z0-9]/', '', (string)$key)] = (array)$item;
    }
} else {
    foreach ($workout->getExercises() as $item) {
        $rows['e' . $item->getId()] = [
            'id' => $item->getId(),
            'exerciseId' => $item->getExerciseId(),
            'sets' => $item->getSets(),
            'repsMin' => $item->getRepsMin(),
            'repsMax' => $item->getRepsMax(),
            'weight' => $item->getWeight(),
            'durationSec' => $item->getDurationSec(),
            'restSec' => $item->getRestSec(),
            'notes' => $item->getNotes(),
        ];
    }
}

$renderRow = function (string $key, array $row) use ($exercises): string {
    $value = fn (string $field) => $this->escape((string)($row[$field] ?? ''));
    $name = fn (string $field) => 'items[' . $key . '][' . $field . ']';

    $options = '<option value="0">' . $this->getTrans('chooseExercise') . '</option>';
    foreach ($exercises as $exercise) {
        $selected = (int)($row['exerciseId'] ?? 0) === $exercise->getId() ? ' selected' : '';
        $inactive = $exercise->isActive() ? '' : ' (' . $this->getTrans('inactive') . ')';
        $options .= '<option value="' . $exercise->getId() . '"' . $selected . '>' . $this->escape($exercise->getTitle()) . $inactive . '</option>';
    }

    return '<tr>
        <td class="fx-handle" title="' . $this->getTrans('dragToSort') . '"><i class="fa-solid fa-grip-vertical"></i></td>
        <td>
            <input type="hidden" name="' . $name('id') . '" value="' . (int)($row['id'] ?? 0) . '">
            <select class="form-select form-select-sm" name="' . $name('exerciseId') . '" aria-label="' . $this->getTrans('exercise') . '">' . $options . '</select>
        </td>
        <td><input type="number" class="form-control form-control-sm" name="' . $name('sets') . '" value="' . $value('sets') . '" min="1" max="99" aria-label="' . $this->getTrans('sets') . '"></td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control" name="' . $name('repsMin') . '" value="' . $value('repsMin') . '" min="0" max="999" aria-label="' . $this->getTrans('repsMin') . '">
                <span class="input-group-text">–</span>
                <input type="number" class="form-control" name="' . $name('repsMax') . '" value="' . $value('repsMax') . '" min="0" max="999" aria-label="' . $this->getTrans('repsMax') . '">
            </div>
        </td>
        <td><input type="text" class="form-control form-control-sm" name="' . $name('weight') . '" value="' . $value('weight') . '" maxlength="50" placeholder="' . $this->getTrans('weightPlaceholder') . '" aria-label="' . $this->getTrans('weight') . '"></td>
        <td><input type="number" class="form-control form-control-sm" name="' . $name('durationSec') . '" value="' . $value('durationSec') . '" min="0" max="86400" aria-label="' . $this->getTrans('durationSec') . '"></td>
        <td><input type="number" class="form-control form-control-sm" name="' . $name('restSec') . '" value="' . $value('restSec') . '" min="0" max="3600" aria-label="' . $this->getTrans('restSec') . '"></td>
        <td><input type="text" class="form-control form-control-sm" name="' . $name('notes') . '" value="' . $value('notes') . '" maxlength="500" aria-label="' . $this->getTrans('notes') . '"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger fx-remove-row" title="' . $this->getTrans('removeExercise') . '"><i class="fa-solid fa-xmark"></i></button></td>
    </tr>';
};
?>
<h1><?=$this->getTrans($workout->getId() ? 'edit' : 'add') ?></h1>
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
                   value="<?=$this->escape($this->originalInput('title', $workout->getTitle())) ?>"
                   required>
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
    <div class="row mb-3<?=$this->validation()->hasError('durationMin') ? ' has-error' : '' ?>">
        <label for="durationMin" class="col-xl-2 col-form-label">
            <?=$this->getTrans('durationMin') ?>:
        </label>
        <div class="col-xl-2">
            <div class="input-group">
                <input type="number"
                       class="form-control"
                       id="durationMin"
                       name="durationMin"
                       min="1"
                       max="1440"
                       value="<?=$this->escape((string)$this->originalInput('durationMin', $workout->getDurationMin())) ?>">
                <span class="input-group-text"><?=$this->getTrans('minutesShort') ?></span>
            </div>
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
                      rows="5"><?=$this->escape($this->originalInput('description', $workout->getDescription())) ?></textarea>
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
    </div>

    <h2 class="h4 mt-4"><?=$this->getTrans('workoutExercises') ?></h2>
    <p class="text-muted"><?=$this->getTrans('workoutExercisesInfo') ?></p>
    <div class="table-responsive<?=$this->validation()->hasError('items') ? ' has-error' : '' ?>">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th></th>
                    <th><?=$this->getTrans('exercise') ?></th>
                    <th><?=$this->getTrans('sets') ?></th>
                    <th><?=$this->getTrans('reps') ?></th>
                    <th><?=$this->getTrans('weight') ?></th>
                    <th><?=$this->getTrans('durationSec') ?></th>
                    <th><?=$this->getTrans('restSec') ?></th>
                    <th><?=$this->getTrans('notes') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="fxWorkoutExercises">
                <?php foreach ($rows as $key => $row) : ?>
                    <?=$renderRow((string)$key, $row) ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p id="fxNoWorkoutExercises" class="text-muted"><?=$this->getTrans('noWorkoutExercises') ?></p>
    <?php if ($exercises) : ?>
        <button type="button" class="btn btn-outline-secondary mb-3" id="fxAddWorkoutExercise">
            <i class="fa-solid fa-plus"></i> <?=$this->getTrans('addExercise') ?>
        </button>
    <?php else : ?>
        <p>
            <?=$this->getTrans('noExercises') ?>
            <a href="<?=$this->getUrl(['controller' => 'exercises', 'action' => 'treat']) ?>"><?=$this->getTrans('add') ?></a>
        </p>
    <?php endif; ?>

    <?=$this->getSaveBar($workout->getId() ? 'updateButton' : 'addButton') ?>
</form>

<template id="fxWorkoutExerciseTemplate">
    <?=$renderRow('__KEY__', []) ?>
</template>

<style>
    .fx-handle { cursor: move; width: 1.5rem; color: #6c757d; }
    #fxWorkoutExercises input[type=number] { min-width: 4.5rem; }
    #fxWorkoutExercises select { min-width: 12rem; }
</style>

<script>
    $(function () {
        const tbody = $('#fxWorkoutExercises');
        const template = document.getElementById('fxWorkoutExerciseTemplate').innerHTML;
        let counter = 0;

        function toggleEmptyNote() {
            $('#fxNoWorkoutExercises').toggle(tbody.children('tr').length === 0);
        }

        $('#fxAddWorkoutExercise').on('click', function () {
            counter++;
            tbody.append(template.replace(/__KEY__/g, 'new' + counter));
            toggleEmptyNote();
        });

        tbody.on('click', '.fx-remove-row', function () {
            $(this).closest('tr').remove();
            toggleEmptyNote();
        });

        tbody.sortable({
            handle: '.fx-handle',
            axis: 'y',
            placeholder: 'table-sort-drop',
            forcePlaceholderSize: true,
            start: function (event, ui) {
                ui.placeholder.html("<td colspan='9'></td>");
                ui.placeholder.height(ui.item.height());
            }
        });

        toggleEmptyNote();
    });
</script>
