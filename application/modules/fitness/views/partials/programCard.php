<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Service\Media;

/** @var \Modules\Fitness\Models\Program $program */
$program = $this->get('program');
$url = $this->getUrl(['module' => 'fitness', 'controller' => 'programs', 'action' => 'show', 'id' => $program->getId()]);
$image = Media::imageUrl($program->getImage(), BASE_URL);
?>
<article class="fx-card fx-program-card">
    <a class="fx-card__media" href="<?=$url ?>" tabindex="-1" aria-hidden="true">
        <?php if ($image) : ?>
            <img src="<?=$this->escape($image) ?>" alt="" loading="lazy">
        <?php else : ?>
            <span class="fx-card__placeholder"><i class="fa-solid fa-dumbbell"></i></span>
        <?php endif; ?>
        <?php if ($program->isPaid()) : ?>
            <span class="fx-badge fx-badge--premium">
                <i class="fa-solid fa-crown"></i> <?=$this->getTrans('premium') ?> · <?=$this->getFormattedCurrency((float)$program->getPrice(), $program->getCurrency()) ?>
            </span>
        <?php else : ?>
            <span class="fx-badge fx-badge--free"><?=$this->getTrans('accessFree') ?></span>
        <?php endif; ?>
    </a>
    <div class="fx-card__body">
        <?php if ($program->getGoal() !== '') : ?>
            <div class="fx-eyebrow"><?=$this->escape($program->getGoal()) ?></div>
        <?php endif; ?>
        <h3 class="fx-card__title"><a href="<?=$url ?>"><?=$this->escape($program->getTitle()) ?></a></h3>
        <?php if ($program->getTeaser() !== '') : ?>
            <p class="fx-card__text"><?=$this->escape($program->getTeaser()) ?></p>
        <?php endif; ?>
        <ul class="fx-meta">
            <li><i class="fa-regular fa-calendar"></i> <?=$program->getPhaseCount() ?> <?=$this->getTrans('phases') ?></li>
            <li><i class="fa-solid fa-dumbbell"></i> <?=$program->getSessionCount() ?> <?=$this->getTrans('sessions') ?></li>
            <li><i class="fa-solid fa-signal"></i> <?=$this->getTrans($program->getDifficultyKey()) ?></li>
        </ul>
    </div>
</article>
