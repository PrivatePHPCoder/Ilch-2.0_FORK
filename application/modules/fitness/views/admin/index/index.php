<?php

/** @var \Ilch\View $this */

/** @var array<string, int> $counts */
$counts = $this->get('counts');
$sections = [
    'exercises' => ['menuExercises', 'fa-solid fa-person-running'],
    'workouts' => ['menuWorkouts', 'fa-solid fa-list-check'],
    'programs' => ['menuPrograms', 'fa-solid fa-calendar-week'],
    'participants' => ['menuParticipants', 'fa-solid fa-users'],
    'orders' => ['ordersOpenCount', 'fa-solid fa-receipt'],
    'milestones' => ['menuMilestones', 'fa-solid fa-medal'],
    'categories' => ['menuCategories', 'fa-solid fa-tags'],
    'musclegroups' => ['menuMuscleGroups', 'fa-solid fa-hand-fist'],
];
?>
<h1><?=$this->getTrans('menuFitness') ?></h1>
<p><?=$this->getTrans('overviewIntro') ?></p>

<div class="row mb-3">
    <?php foreach ($sections as $controller => [$nameKey, $icon]) : ?>
        <div class="col-6 col-md-4 col-xl-3 mb-3">
            <a class="card text-decoration-none h-100" href="<?=$this->getUrl(array_merge(['controller' => $controller, 'action' => 'index'], $controller === 'orders' ? ['status' => 'open'] : [])) ?>">
                <div class="card-body">
                    <div class="fs-2 fw-bold"><?=$counts[$controller] ?></div>
                    <div class="text-muted"><i class="<?=$icon ?>"></i> <?=$this->getTrans($nameKey) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<p>
    <i class="fa-solid fa-palette"></i>
    <?=$this->getTrans($this->get('ownLayout') ? 'overviewLayoutOn' : 'overviewLayoutOff') ?>
    <a href="<?=$this->getUrl(['controller' => 'settings', 'action' => 'index']) ?>"><?=$this->getTrans('menuSettings') ?></a>
</p>
