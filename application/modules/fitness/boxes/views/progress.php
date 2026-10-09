<?php

/** @var \Ilch\View $this */

/** @var \Modules\User\Models\User|null $user */
$user = $this->get('user');
/** @var array $trainings see Service\Trainings::getOfUser() */
$trainings = $this->get('trainings');
?>
<?php if (!$user) : ?>
    <p><?=$this->getTrans('boxGuestText') ?></p>
    <a href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'programs', 'action' => 'index']) ?>"><?=$this->getTrans('showPrograms') ?></a>
<?php else : ?>
    <?php if ($trainings) : ?>
        <?php foreach ($trainings as $training) : ?>
            <?php
            /** @var \Modules\Fitness\Models\Progress $progress */
            $progress = $training['progress'];
            $nextSession = $progress->getNextSession();
            ?>
            <div class="mb-3">
                <a class="fw-bold" href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'programs', 'action' => 'show', 'id' => $training['program']->getId()]) ?>"><?=$this->escape($training['program']->getTitle()) ?></a>
                <div class="progress my-1" style="height: 0.5rem;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?=$progress->getPercent() ?>" aria-label="<?=$this->getTrans('progress') ?>">
                    <div class="progress-bar bg-success" style="width: <?=$progress->getPercent() ?>%"></div>
                </div>
                <small class="text-muted">
                    <?=$progress->getPercent() ?> %
                    <?php if ($nextSession) : ?>
                        · <a href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'training', 'action' => 'session', 'id' => $nextSession->getId()]) ?>"><?=$this->getTrans('nextSession') ?>: <?=$this->escape($nextSession->getDisplayTitle()) ?></a>
                    <?php endif; ?>
                </small>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p><?=$this->getTrans('boxNoActiveTraining') ?></p>
        <a href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'programs', 'action' => 'index']) ?>"><?=$this->getTrans('showPrograms') ?></a>
    <?php endif; ?>
    <hr />
    <div class="text-center">
        <a href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'training', 'action' => 'index']) ?>"><?=$this->getTrans('myTraining') ?></a>
        ·
        <a href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'milestones', 'action' => 'index']) ?>"><i class="fa-solid fa-medal"></i> <?=$this->getTrans('boxMilestoneCount', (int)$this->get('milestoneCount')) ?></a>
    </div>
<?php endif; ?>
