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
/** @var array<string, int> $sampleCounts number of sample entries per kind */
$sampleCounts = $this->get('sampleCounts');
$sampleLabels = [
    'programs' => 'menuPrograms',
    'workouts' => 'menuWorkouts',
    'exercises' => 'menuExercises',
    'categories' => 'menuCategories',
    'muscleGroups' => 'menuMuscleGroups',
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

<div class="card mt-4">
    <div class="card-body">
        <h2 class="h5"><i class="fa-solid fa-flask"></i> <?=$this->getTrans('sampleData') ?></h2>
        <?php if (array_sum($sampleCounts)) : ?>
            <p><?=$this->getTrans('sampleDataInstalledInfo') ?></p>
            <ul>
                <?php foreach ($sampleLabels as $type => $labelKey) : ?>
                    <?php if ($sampleCounts[$type]) : ?>
                        <li><?=$sampleCounts[$type] ?> <?=$this->getTrans($labelKey) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <?php if ($this->get('sampleParticipants')) : ?>
                <p class="text-warning-emphasis"><i class="fa-solid fa-triangle-exclamation"></i> <?=$this->getTrans('sampleDataParticipants', (int)$this->get('sampleParticipants')) ?></p>
            <?php endif; ?>
            <form method="POST" action="<?=$this->getUrl(['controller' => 'sampledata', 'action' => 'remove']) ?>">
                <?=$this->getTokenField() ?>
                <button type="submit" class="btn btn-outline-danger" data-confirm="<?=$this->getTrans('sampleDataRemoveConfirm') ?>">
                    <i class="fa-solid fa-trash-can"></i> <?=$this->getTrans('sampleDataRemove') ?>
                </button>
            </form>
        <?php else : ?>
            <p><?=$this->getTrans('sampleDataInfo') ?></p>
            <form method="POST" action="<?=$this->getUrl(['controller' => 'sampledata', 'action' => 'install']) ?>">
                <?=$this->getTokenField() ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> <?=$this->getTrans('sampleDataInstall') ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    $(function () {
        $('button[data-confirm]').on('click', function (event) {
            if (!window.confirm($(this).data('confirm'))) {
                event.preventDefault();
            }
        });
    });
</script>
