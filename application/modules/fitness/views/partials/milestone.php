<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Milestone $milestone */
$milestone = $this->get('milestone');
/** @var string|null $achievedAt date and time it was reached, null if still open */
$achievedAt = $this->get('achievedAt');
/** @var int|null $current how far the user is, only for open milestones */
$current = $this->get('current');
$threshold = max(1, $milestone->getThreshold());
$percent = $current !== null ? (int)floor(min($current, $threshold) * 100 / $threshold) : 0;
?>
<article class="fx-milestone<?=$achievedAt ? ' is-reached' : '' ?>">
    <div class="fx-milestone__icon" aria-hidden="true"><i class="<?=$this->escape($milestone->getIcon()) ?>"></i></div>
    <div class="fx-milestone__body">
        <h3 class="fx-milestone__title"><?=$this->escape($milestone->getDisplayTitle($this->getTranslator())) ?></h3>
        <p class="fx-milestone__text">
            <?php if ($milestone->getDescription() !== '') : ?>
                <?=$this->escape($milestone->getDescription()) ?>
            <?php else : ?>
                <?=$this->getTrans($milestone->getConditionKey(), $milestone->getThreshold()) ?>
            <?php endif; ?>
        </p>
        <?php if (!$milestone->isForAllPrograms()) : ?>
            <span class="fx-badge fx-badge--soft"><i class="fa-solid fa-calendar-week"></i> <?=$this->escape($milestone->getProgramTitle()) ?></span>
        <?php endif; ?>
        <?php if ($achievedAt) : ?>
            <div class="fx-milestone__date">
                <i class="fa-solid fa-circle-check"></i> <?=$this->getTrans('reachedOn', (new \Ilch\Date($achievedAt))->format('d.m.Y', true)) ?>
            </div>
        <?php elseif ($current !== null) : ?>
            <div class="fx-progress fx-progress--slim" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?=$percent ?>" aria-label="<?=$this->getTrans('progress') ?>">
                <div class="fx-progress__bar" style="width: <?=$percent ?>%"></div>
            </div>
            <div class="fx-milestone__count"><?=$this->getTrans($milestone->getProgressKey(), min($current, $threshold), $threshold) ?></div>
        <?php endif; ?>
    </div>
</article>
