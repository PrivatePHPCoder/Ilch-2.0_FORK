<?php

/** @var \Ilch\View $this */

/** @var array{enrollment: \Modules\Fitness\Models\Enrollment, program: \Modules\Fitness\Models\Program, progress: \Modules\Fitness\Models\Progress} $training */
$training = $this->get('training');
$enrollment = $training['enrollment'];
$program = $training['program'];
$progress = $training['progress'];
$nextSession = $progress->getNextSession();
$currentPhase = $progress->getCurrentPhase();
?>
<article class="fx-card fx-card--padded fx-training-card">
    <div class="fx-training-card__head">
        <div>
            <div class="fx-eyebrow"><?=$this->getTrans('myProgram') ?></div>
            <h3 class="fx-card__title">
                <a href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'programs', 'action' => 'show', 'id' => $program->getId()]) ?>"><?=$this->escape($program->getTitle()) ?></a>
            </h3>
        </div>
        <?php if ($enrollment->getStatus() !== \Modules\Fitness\Models\Enrollment::STATUS_ACTIVE) : ?>
            <span class="fx-status fx-status--<?=$enrollment->getStatus() ?>"><?=$this->getTrans($enrollment->getStatusKey()) ?></span>
        <?php endif; ?>
    </div>

    <div class="fx-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?=$progress->getPercent() ?>" aria-label="<?=$this->getTrans('progress') ?>">
        <div class="fx-progress__bar" style="width: <?=$progress->getPercent() ?>%"></div>
    </div>
    <div class="fx-progress__legend">
        <strong><?=$progress->getPercent() ?> %</strong>
        <span><?=$this->getTrans('sessionsDoneOf', $progress->getDone(), $progress->getTotal()) ?></span>
    </div>

    <?php if ($nextSession && $enrollment->grantsAccess()) : ?>
        <p class="fx-training-card__next">
            <?php if ($currentPhase) : ?>
                <span class="fx-muted"><?=$this->getTrans('currentPhase') ?>:</span> <?=$this->escape($currentPhase->getTitle()) ?><br>
            <?php endif; ?>
            <span class="fx-muted"><?=$this->getTrans('nextSession') ?>:</span> <?=$this->escape($nextSession->getDisplayTitle()) ?>
        </p>
        <a class="btn fx-btn fx-btn--primary" href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'training', 'action' => 'session', 'id' => $nextSession->getId()]) ?>">
            <i class="fa-solid fa-play"></i> <?=$this->getTrans($progress->getDone() > 0 ? 'continueTraining' : 'startTraining') ?>
        </a>
    <?php elseif ($progress->isComplete()) : ?>
        <p class="fx-training-card__next"><i class="fa-solid fa-trophy"></i> <?=$this->getTrans('programDoneText') ?></p>
    <?php endif; ?>
</article>
