<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\ProgramSession;

/** @var \Modules\Fitness\Models\Program $program */
$program = $this->get('program');
/** @var \Modules\Fitness\Models\ProgramPhase[] $phases */
$phases = $this->get('phases');
/** @var array<int, \Modules\Fitness\Models\Workout> $workouts */
$workouts = $this->get('workouts');

// Phases with sessions as plain arrays: after a failed validation from the old input, otherwise from the database.
$phaseRows = [];
$oldPhases = $this->originalInput('phases', null);
if (is_array($oldPhases)) {
    foreach ($oldPhases as $phaseKey => $phase) {
        $phase = (array)$phase;
        $sessions = [];
        foreach ((array)($phase['sessions'] ?? []) as $sessionKey => $session) {
            $sessions[preg_replace('/[^A-Za-z0-9]/', '', (string)$sessionKey)] = (array)$session;
        }
        $phase['sessions'] = $sessions;
        $phaseRows[preg_replace('/[^A-Za-z0-9]/', '', (string)$phaseKey)] = $phase;
    }
} else {
    foreach ($phases as $phase) {
        $sessions = [];
        foreach ($phase->getSessions() as $session) {
            $sessions['s' . $session->getId()] = [
                'id' => $session->getId(),
                'workoutId' => $session->getWorkoutId(),
                'title' => $session->getTitle(),
                'dayHint' => $session->getDayHint(),
                'optional' => $session->isOptional(),
            ];
        }
        $phaseRows['p' . $phase->getId()] = [
            'id' => $phase->getId(),
            'title' => $phase->getTitle(),
            'description' => $phase->getDescription(),
            'sessions' => $sessions,
        ];
    }
}

$workoutOptions = function (int $selectedId) use ($workouts): string {
    $html = '<option value="0">' . $this->getTrans('chooseWorkout') . '</option>';
    foreach ($workouts as $workout) {
        $inactive = $workout->isActive() ? '' : ' (' . $this->getTrans('inactive') . ')';
        $html .= '<option value="' . $workout->getId() . '"' . ($selectedId === $workout->getId() ? ' selected' : '') . '>'
            . $this->escape($workout->getTitle()) . $inactive . '</option>';
    }
    return $html;
};

$dayOptions = function (?int $selectedDay): string {
    $html = '<option value="">' . $this->getTrans('noDay') . '</option>';
    foreach (ProgramSession::DAYS as $day => $translationKey) {
        $html .= '<option value="' . $day . '"' . ($selectedDay === $day ? ' selected' : '') . '>' . $this->getTrans($translationKey) . '</option>';
    }
    return $html;
};

$renderSession = function (string $phaseKey, string $sessionKey, array $session) use ($workoutOptions, $dayOptions): string {
    $name = fn (string $field) => 'phases[' . $phaseKey . '][sessions][' . $sessionKey . '][' . $field . ']';
    $dayHint = isset($session['dayHint']) && $session['dayHint'] !== '' ? (int)$session['dayHint'] : null;

    return '<tr>
        <td class="fx-handle" title="' . $this->getTrans('dragToSort') . '"><i class="fa-solid fa-grip-vertical"></i></td>
        <td>
            <input type="hidden" name="' . $name('id') . '" value="' . (int)($session['id'] ?? 0) . '">
            <input type="text" class="form-control form-control-sm" name="' . $name('title') . '" value="' . $this->escape((string)($session['title'] ?? '')) . '" maxlength="255" placeholder="' . $this->getTrans('sessionTitlePlaceholder') . '" aria-label="' . $this->getTrans('sessionTitle') . '">
        </td>
        <td><select class="form-select form-select-sm" name="' . $name('workoutId') . '" aria-label="' . $this->getTrans('workout') . '">' . $workoutOptions((int)($session['workoutId'] ?? 0)) . '</select></td>
        <td><select class="form-select form-select-sm" name="' . $name('dayHint') . '" aria-label="' . $this->getTrans('dayHint') . '">' . $dayOptions($dayHint) . '</select></td>
        <td class="text-center"><input class="form-check-input" type="checkbox" name="' . $name('optional') . '" value="1"' . (!empty($session['optional']) ? ' checked' : '') . ' aria-label="' . $this->getTrans('optional') . '"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger fx-remove-session" title="' . $this->getTrans('removeSession') . '"><i class="fa-solid fa-xmark"></i></button></td>
    </tr>';
};

$renderPhase = function (string $phaseKey, array $phase, string $defaultTitle = '') use ($renderSession): string {
    $sessionsHtml = '';
    foreach ((array)($phase['sessions'] ?? []) as $sessionKey => $session) {
        $sessionsHtml .= $renderSession($phaseKey, (string)$sessionKey, (array)$session);
    }
    $title = (string)($phase['title'] ?? $defaultTitle);

    return '<div class="card mb-3 fx-phase" data-key="' . $phaseKey . '">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="fx-phase-handle" title="' . $this->getTrans('dragToSort') . '"><i class="fa-solid fa-grip-vertical"></i></span>
            <input type="hidden" name="phases[' . $phaseKey . '][id]" value="' . (int)($phase['id'] ?? 0) . '">
            <input type="text" class="form-control form-control-sm fw-bold" name="phases[' . $phaseKey . '][title]" value="' . $this->escape($title) . '" maxlength="255" required aria-label="' . $this->getTrans('phaseTitle') . '">
            <button type="button" class="btn btn-sm btn-outline-danger fx-remove-phase" title="' . $this->getTrans('removePhase') . '"><i class="fa-solid fa-trash-can"></i></button>
        </div>
        <div class="card-body">
            <input type="text" class="form-control form-control-sm mb-2" name="phases[' . $phaseKey . '][description]" value="' . $this->escape((string)($phase['description'] ?? '')) . '" maxlength="1000" placeholder="' . $this->getTrans('phaseDescriptionPlaceholder') . '" aria-label="' . $this->getTrans('description') . '">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-2">
                    <thead>
                        <tr>
                            <th></th>
                            <th>' . $this->getTrans('sessionTitle') . '</th>
                            <th>' . $this->getTrans('workout') . '</th>
                            <th>' . $this->getTrans('dayHint') . '</th>
                            <th class="text-center">' . $this->getTrans('optional') . '</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="fx-sessions">' . $sessionsHtml . '</tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary fx-add-session"><i class="fa-solid fa-plus"></i> ' . $this->getTrans('addSession') . '</button>
        </div>
    </div>';
};
?>
<h1><?=$this->getTrans('programStructure') ?>: <?=$this->escape($program->getTitle()) ?></h1>
<p class="text-muted"><?=$this->getTrans('programStructureInfo') ?></p>

<?php if ($this->get('hasParticipants')) : ?>
    <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> <?=$this->getTrans('structureHasParticipants') ?></div>
<?php endif; ?>

<?php if (!$workouts) : ?>
    <div class="alert alert-info">
        <?=$this->getTrans('noWorkouts') ?>
        <a href="<?=$this->getUrl(['controller' => 'workouts', 'action' => 'treat']) ?>"><?=$this->getTrans('add') ?></a>
    </div>
<?php endif; ?>

<form method="POST" id="fxStructureForm">
    <?=$this->getTokenField() ?>
    <div id="fxPhases" class="<?=$this->validation()->hasError('phases') ? 'has-error' : '' ?>">
        <?php foreach ($phaseRows as $phaseKey => $phase) : ?>
            <?=$renderPhase((string)$phaseKey, $phase) ?>
        <?php endforeach; ?>
    </div>
    <p id="fxNoPhases" class="text-muted"><?=$this->getTrans('noPhases') ?></p>
    <button type="button" class="btn btn-outline-secondary mb-4" id="fxAddPhase"><i class="fa-solid fa-plus"></i> <?=$this->getTrans('addPhase') ?></button>

    <div class="content_savebox">
        <button type="submit" class="save_button btn btn-secondary" name="saveStructure" value="1"><?=$this->getTrans('saveButton') ?></button>
        <a class="btn btn-outline-secondary" href="<?=$this->getUrl(['action' => 'treat', 'id' => $program->getId()]) ?>"><?=$this->getTrans('editProgramData') ?></a>
    </div>
</form>

<?php if ($workouts) : ?>
    <div class="card mb-5">
        <div class="card-header"><i class="fa-solid fa-wand-magic-sparkles"></i> <?=$this->getTrans('generateWeeks') ?></div>
        <div class="card-body">
            <p class="text-muted"><?=$this->getTrans('generateWeeksInfo') ?></p>
            <form method="POST">
                <?=$this->getTokenField() ?>
                <div class="row mb-3">
                    <label for="weeks" class="col-xl-2 col-form-label"><?=$this->getTrans('numberOfWeeks') ?>:</label>
                    <div class="col-xl-2">
                        <input type="number" class="form-control" id="weeks" name="weeks" min="1" max="<?=(int)$this->get('maxGeneratedWeeks') ?>" value="8" required>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th><?=$this->getTrans('sessionTitle') ?></th>
                                <th><?=$this->getTrans('workout') ?></th>
                                <th><?=$this->getTrans('dayHint') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (['A' => 1, 'B' => 3, 'C' => 5] as $letter => $day) : ?>
                                <tr>
                                    <td><input type="text" class="form-control form-control-sm" name="template[<?=$letter ?>][title]" value="<?=$this->escape($this->getTrans('trainingLetter', $letter)) ?>" maxlength="255" aria-label="<?=$this->getTrans('sessionTitle') ?>"></td>
                                    <td><select class="form-select form-select-sm" name="template[<?=$letter ?>][workoutId]" aria-label="<?=$this->getTrans('workout') ?>"><?=$workoutOptions(0) ?></select></td>
                                    <td><select class="form-select form-select-sm" name="template[<?=$letter ?>][dayHint]" aria-label="<?=$this->getTrans('dayHint') ?>"><?=$dayOptions($day) ?></select></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="form-text"><?=$this->getTrans('generateWeeksRowsInfo') ?></p>
                <button type="submit" class="btn btn-outline-primary" name="generate" value="1"><i class="fa-solid fa-plus"></i> <?=$this->getTrans('appendWeeks') ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>

<template id="fxPhaseTemplate">
    <?=$renderPhase('__PKEY__', ['sessions' => []], $this->getTrans('weekNumber', '__NUMBER__')) ?>
</template>
<template id="fxSessionTemplate">
    <?=$renderSession('__PKEY__', '__SKEY__', []) ?>
</template>

<style>
    .fx-handle, .fx-phase-handle { cursor: move; width: 1.5rem; color: #6c757d; }
    .fx-sessions select { min-width: 11rem; }
</style>

<script>
    $(function () {
        const phasesBox = $('#fxPhases');
        const phaseTemplate = document.getElementById('fxPhaseTemplate').innerHTML;
        const sessionTemplate = document.getElementById('fxSessionTemplate').innerHTML;
        let counter = 0;

        function makeSessionsSortable(scope) {
            scope.find('.fx-sessions').sortable({ handle: '.fx-handle', axis: 'y' });
        }

        function toggleEmptyNote() {
            $('#fxNoPhases').toggle(phasesBox.children('.fx-phase').length === 0);
        }

        $('#fxAddPhase').on('click', function () {
            counter++;
            const number = phasesBox.children('.fx-phase').length + 1;
            const phase = $(phaseTemplate.replace(/__PKEY__/g, 'new' + counter).replace(/__NUMBER__/g, number)).appendTo(phasesBox);
            makeSessionsSortable(phase);
            toggleEmptyNote();
        });

        phasesBox.on('click', '.fx-add-session', function () {
            counter++;
            const phase = $(this).closest('.fx-phase');
            phase.find('.fx-sessions').append(sessionTemplate.replace(/__PKEY__/g, phase.data('key')).replace(/__SKEY__/g, 'new' + counter));
        });

        phasesBox.on('click', '.fx-remove-session', function () {
            $(this).closest('tr').remove();
        });

        phasesBox.on('click', '.fx-remove-phase', function () {
            if (confirm(<?=json_encode($this->getTrans('removePhaseConfirm')) ?>)) {
                $(this).closest('.fx-phase').remove();
                toggleEmptyNote();
            }
        });

        phasesBox.sortable({ handle: '.fx-phase-handle', items: '> .fx-phase', axis: 'y' });
        makeSessionsSortable(phasesBox);
        toggleEmptyNote();
    });
</script>
