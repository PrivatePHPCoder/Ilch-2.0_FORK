<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Service\Media;

/** @var \Modules\Fitness\Models\Program $program */
$program = $this->get('program');
/** @var \Modules\Fitness\Models\ProgramSession $session */
$session = $this->get('session');
/** @var \Modules\Fitness\Models\ProgramPhase|null $phase */
$phase = $this->get('phase');
/** @var \Modules\Fitness\Models\Workout $workout */
$workout = $this->get('workout');
/** @var array<int, \Modules\Fitness\Models\Exercise> $exercises */
$exercises = $this->get('exercises');
/** @var array<int, \Modules\Fitness\Models\MuscleGroup> $muscleGroups */
$muscleGroups = $this->get('muscleGroups');
/** @var \Modules\Fitness\Models\Enrollment|null $enrollment */
$enrollment = $this->get('enrollment');
/** @var \Modules\Fitness\Models\Progress $progress */
$progress = $this->get('progress');
/** @var \Modules\Fitness\Models\ProgramSession|null $previousSession */
$previousSession = $this->get('previousSession');
/** @var \Modules\Fitness\Models\ProgramSession|null $nextSession */
$nextSession = $this->get('nextSession');
$doneAt = $this->get('doneAt');
/** @var array<int, string> $phaseTitles */
$phaseTitles = $this->get('phaseTitles');

$sessionLabel = function (\Modules\Fitness\Models\ProgramSession $other) use ($phaseTitles): string {
    $phaseTitle = $phaseTitles[$other->getPhaseId()] ?? '';

    return $this->escape(($phaseTitle !== '' ? $phaseTitle . ' · ' : '') . $other->getDisplayTitle());
};

$formatSeconds = function (int $seconds): string {
    if ($seconds >= 60) {
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60) . ' ' . $this->getTrans('minutesShort');
    }

    return $seconds . ' ' . $this->getTrans('secondsShort');
};
?>
<?php if (!$enrollment) : ?>
    <div class="alert alert-warning"><i class="fa-solid fa-eye"></i> <?=$this->getTrans('previewSession') ?></div>
<?php endif; ?>
<?php $this->load('partials/reachedMilestones.php', ['milestones' => $this->get('reachedMilestones')]); ?>

<header class="fx-session-head">
    <div class="fx-eyebrow">
        <?=$this->escape($program->getTitle()) ?><?=$phase ? ' · ' . $this->escape($phase->getTitle()) : '' ?>
    </div>
    <h1 class="fx-page-title">
        <?=$this->escape($session->getDisplayTitle()) ?>
        <?php if ($session->isOptional()) : ?>
            <span class="fx-badge fx-badge--soft"><?=$this->getTrans('optional') ?></span>
        <?php endif; ?>
    </h1>
    <ul class="fx-meta">
        <?php if ($session->getTitle() !== '' && $session->getTitle() !== $workout->getTitle()) : ?>
            <li><i class="fa-solid fa-list-check"></i> <?=$this->escape($workout->getTitle()) ?></li>
        <?php endif; ?>
        <li><i class="fa-solid fa-dumbbell"></i> <?=count($workout->getExercises()) ?> <?=$this->getTrans('exercises') ?></li>
        <?php if ($workout->getDurationMin()) : ?>
            <li><i class="fa-regular fa-clock"></i> <?=$workout->getDurationMin() ?> <?=$this->getTrans('minutesShort') ?></li>
        <?php endif; ?>
        <li><i class="fa-solid fa-signal"></i> <?=$this->getTrans($workout->getDifficultyKey()) ?></li>
    </ul>
    <?php if ($enrollment) : ?>
        <div class="fx-progress fx-progress--slim" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?=$progress->getPercent() ?>" aria-label="<?=$this->getTrans('progress') ?>">
            <div class="fx-progress__bar" style="width: <?=$progress->getPercent() ?>%"></div>
        </div>
        <div class="fx-progress__legend">
            <strong><?=$progress->getPercent() ?> %</strong>
            <span><?=$this->getTrans('sessionsDoneOf', $progress->getDone(), $progress->getTotal()) ?></span>
        </div>
    <?php endif; ?>
</header>

<?php if ($workout->getDescription() !== '') : ?>
    <section class="fx-prose mb-4"><?=$this->purify($workout->getDescription()) ?></section>
<?php endif; ?>

<?php if ($workout->getExercises()) : ?>
    <ol class="fx-steps">
        <?php foreach ($workout->getExercises() as $number => $item) : ?>
            <?php
            $exercise = $exercises[$item->getExerciseId()] ?? null;
            if (!$exercise) {
                continue;
            }
            $image = Media::imageUrl($exercise->getImage(), BASE_URL);
            $video = Media::videoEmbed($exercise->getVideoUrl());
            $prescription = [];
            if ($item->getSets()) {
                $prescription[] = $this->getTrans('setsCount', $item->getSets());
            }
            if ($item->getRepsText() !== '') {
                $prescription[] = $this->getTrans('repsCount', $item->getRepsText());
            }
            if ($item->getWeight() !== '') {
                $prescription[] = $this->escape($item->getWeight());
            }
            if ($item->getDurationSec()) {
                $prescription[] = $formatSeconds($item->getDurationSec());
            }
            $hasDetails = $exercise->getDescription() !== '' || $exercise->getInstructions() !== '' || $exercise->getNotes() !== '' || $video;
            ?>
            <li class="fx-card fx-step">
                <div class="fx-step__head">
                    <span class="fx-step__number"><?=$number + 1 ?></span>
                    <div class="fx-step__main">
                        <h2 class="fx-step__title"><?=$this->escape($exercise->getTitle()) ?></h2>
                        <?php if ($prescription) : ?>
                            <div class="fx-prescription">
                                <?php foreach ($prescription as $value) : ?>
                                    <span class="fx-prescription__item"><?=$value ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($item->getRestSec()) : ?>
                            <div class="fx-step__rest"><i class="fa-solid fa-hourglass-half"></i> <?=$this->getTrans('restAfter', $formatSeconds($item->getRestSec())) ?></div>
                        <?php endif; ?>
                        <?php if ($item->getNotes() !== '') : ?>
                            <p class="fx-step__notes"><i class="fa-regular fa-comment"></i> <?=$this->escape($item->getNotes()) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($image) : ?>
                        <img class="fx-step__image" src="<?=$this->escape($image) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                </div>
                <?php if ($hasDetails) : ?>
                    <details class="fx-step__details">
                        <summary><i class="fa-solid fa-book-open"></i> <?=$this->getTrans('showInstructions') ?></summary>
                        <div class="fx-step__details-body">
                            <?php if ($video) : ?>
                                <div class="fx-video mb-3" data-src="<?=$this->escape($video['url']) ?>" data-title="<?=$this->escape($exercise->getTitle()) ?>">
                                    <div class="fx-video__consent">
                                        <i class="fa-solid fa-circle-play"></i>
                                        <p><?=$this->getTrans('videoConsent', $video['provider'], $video['provider']) ?></p>
                                        <button type="button" class="btn fx-btn fx-btn--primary fx-video-load"><?=$this->getTrans('loadVideo') ?></button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($exercise->getDescription() !== '') : ?>
                                <div class="fx-prose"><?=$this->purify($exercise->getDescription()) ?></div>
                            <?php endif; ?>
                            <?php if ($exercise->getInstructions() !== '') : ?>
                                <h3 class="fx-section-title mt-3"><?=$this->getTrans('howTo') ?></h3>
                                <div class="fx-prose"><?=$this->purify($exercise->getInstructions()) ?></div>
                            <?php endif; ?>
                            <?php if ($exercise->getNotes() !== '') : ?>
                                <div class="fx-note mt-3">
                                    <h3 class="fx-note__title"><i class="fa-solid fa-triangle-exclamation"></i> <?=$this->getTrans('notes') ?></h3>
                                    <p class="mb-0"><?=nl2br($this->escape($exercise->getNotes())) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($exercise->getMuscleGroupIds()) : ?>
                                <div class="fx-chips mt-3">
                                    <?php foreach ($exercise->getMuscleGroupIds() as $muscleGroupId) : ?>
                                        <?php if (isset($muscleGroups[$muscleGroupId])) : ?>
                                            <span class="fx-chip is-static<?=$muscleGroupId === $exercise->getPrimaryMuscleGroupId() ? ' is-primary' : '' ?>"><?=$this->escape($muscleGroups[$muscleGroupId]->getName()) ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
<?php else : ?>
    <p class="fx-empty"><?=$this->getTrans('workoutEmpty') ?></p>
<?php endif; ?>

<section class="fx-card fx-card--padded fx-session-actions">
    <?php if ($enrollment) : ?>
        <?php if ($doneAt) : ?>
            <div class="fx-session-done">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong><?=$this->getTrans('sessionDoneTitle') ?></strong>
                    <span class="fx-muted"><?=$this->getTrans('doneOn', (new \Ilch\Date($doneAt))->format('d.m.Y H:i', true)) ?></span>
                </div>
            </div>
            <?php if ($enrollment->canLogSessions()) : ?>
                <form method="POST" action="<?=$this->getUrl(['action' => 'undo', 'id' => $session->getId()]) ?>">
                    <?=$this->getTokenField() ?>
                    <button type="submit" class="btn btn-link fx-undo"><?=$this->getTrans('undoSession') ?></button>
                </form>
            <?php endif; ?>
        <?php elseif ($enrollment->canLogSessions()) : ?>
            <form method="POST" action="<?=$this->getUrl(['action' => 'complete', 'id' => $session->getId()]) ?>">
                <?=$this->getTokenField() ?>
                <button type="submit" class="btn fx-btn fx-btn--primary fx-btn--large">
                    <i class="fa-solid fa-check"></i> <?=$this->getTrans('markSessionDone') ?>
                </button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <nav class="fx-session-nav" aria-label="<?=$this->getTrans('sessionNavigation') ?>">
        <?php if ($previousSession) : ?>
            <a href="<?=$this->getUrl(['action' => 'session', 'id' => $previousSession->getId()]) ?>"><i class="fa-solid fa-arrow-left"></i> <?=$sessionLabel($previousSession) ?></a>
        <?php else : ?>
            <span></span>
        <?php endif; ?>
        <a href="<?=$this->getUrl(['controller' => 'programs', 'action' => 'show', 'id' => $program->getId()]) ?>"><?=$this->getTrans('toProgram') ?></a>
        <?php if ($nextSession) : ?>
            <a href="<?=$this->getUrl(['action' => 'session', 'id' => $nextSession->getId()]) ?>"><?=$sessionLabel($nextSession) ?> <i class="fa-solid fa-arrow-right"></i></a>
        <?php else : ?>
            <span></span>
        <?php endif; ?>
    </nav>
</section>
