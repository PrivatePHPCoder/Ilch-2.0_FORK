<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Milestone[] $reached */
$reached = $this->get('reached');
/** @var array<int, array{milestone: \Modules\Fitness\Models\Milestone, current: int}> $open */
$open = $this->get('open');
/** @var array<int, string> $achievements */
$achievements = $this->get('achievements');
/** @var array{sessions: int, phases: int, programs: int} $totals */
$totals = $this->get('totals');
?>
<?php $this->load('partials/visitorBar.php', ['active' => $this->get('visitorView')]); ?>
<header class="fx-page-head">
    <h1 class="fx-page-title"><?=$this->getTrans('myMilestones') ?></h1>
    <p class="fx-page-lead"><?=$this->getTrans('myMilestonesLead') ?></p>
</header>

<div class="fx-stats">
    <div class="fx-card fx-stats__item">
        <strong><?=count($reached) ?><small> / <?=(int)$this->get('milestoneCount') ?></small></strong>
        <span><i class="fa-solid fa-medal"></i> <?=$this->getTrans('statMilestones') ?></span>
    </div>
    <div class="fx-card fx-stats__item">
        <strong><?=$totals['sessions'] ?></strong>
        <span><i class="fa-solid fa-dumbbell"></i> <?=$this->getTrans('statSessions') ?></span>
    </div>
    <div class="fx-card fx-stats__item">
        <strong><?=$totals['phases'] ?></strong>
        <span><i class="fa-solid fa-calendar-check"></i> <?=$this->getTrans('statPhases') ?></span>
    </div>
    <div class="fx-card fx-stats__item">
        <strong><?=$totals['programs'] ?></strong>
        <span><i class="fa-solid fa-trophy"></i> <?=$this->getTrans('statPrograms') ?></span>
    </div>
</div>

<section class="fx-section">
    <h2 class="fx-section-title"><?=$this->getTrans('milestonesReached') ?></h2>
    <?php if ($reached) : ?>
        <div class="fx-milestones">
            <?php foreach ($reached as $milestone) : ?>
                <?php $this->load('partials/milestone.php', ['milestone' => $milestone, 'achievedAt' => $achievements[$milestone->getId()]]); ?>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="fx-empty"><?=$this->getTrans('noMilestonesYet') ?></p>
    <?php endif; ?>
</section>

<?php if ($open) : ?>
    <section class="fx-section">
        <h2 class="fx-section-title"><?=$this->getTrans('milestonesOpen') ?></h2>
        <div class="fx-milestones">
            <?php foreach ($open as $item) : ?>
                <?php $this->load('partials/milestone.php', ['milestone' => $item['milestone'], 'current' => $item['current']]); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
