<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Service\Media;

/** @var \Modules\Fitness\Models\Exercise $exercise */
$exercise = $this->get('exercise');
/** @var array<int, \Modules\Fitness\Models\MuscleGroup> $muscleGroups */
$muscleGroups = $this->get('muscleGroups');
$image = Media::imageUrl($exercise->getImage(), BASE_URL);
$video = Media::videoEmbed($exercise->getVideoUrl());
?>
<?php $this->load('partials/visitorBar.php', ['active' => $this->get('visitorView')]); ?>
<?php if ($this->get('isPreview')) : ?>
    <div class="alert alert-warning"><i class="fa-solid fa-eye"></i> <?=$this->getTrans('previewExercise') ?></div>
<?php endif; ?>

<header class="fx-page-head">
    <?php if ($exercise->getCategoryName() !== '') : ?>
        <div class="fx-eyebrow"><?=$this->escape($exercise->getCategoryName()) ?></div>
    <?php endif; ?>
    <h1 class="fx-page-title"><?=$this->escape($exercise->getTitle()) ?></h1>
    <div class="fx-chips">
        <span class="fx-chip is-static"><i class="fa-solid fa-signal"></i> <?=$this->getTrans($exercise->getDifficultyKey()) ?></span>
        <?php foreach ($exercise->getMuscleGroupIds() as $muscleGroupId) : ?>
            <?php if (isset($muscleGroups[$muscleGroupId])) : ?>
                <span class="fx-chip is-static<?=$muscleGroupId === $exercise->getPrimaryMuscleGroupId() ? ' is-primary' : '' ?>">
                    <?php if ($muscleGroupId === $exercise->getPrimaryMuscleGroupId()) : ?>
                        <i class="fa-solid fa-bullseye"></i>
                    <?php endif; ?>
                    <?=$this->escape($muscleGroups[$muscleGroupId]->getName()) ?>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</header>

<div class="fx-split">
    <div class="fx-split__half">
        <?php if ($video) : ?>
            <div class="fx-video" data-src="<?=$this->escape($video['url']) ?>" data-title="<?=$this->escape($exercise->getTitle()) ?>">
                <div class="fx-video__consent">
                    <i class="fa-solid fa-circle-play"></i>
                    <p><?=$this->getTrans('videoConsent', $video['provider'], $video['provider']) ?></p>
                    <button type="button" class="btn fx-btn fx-btn--primary fx-video-load"><?=$this->getTrans('loadVideo') ?></button>
                </div>
            </div>
        <?php elseif ($exercise->getVideoUrl() !== '') : ?>
            <p><a class="btn fx-btn fx-btn--ghost-dark" href="<?=$this->escape($exercise->getVideoUrl()) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-up-right-from-square"></i> <?=$this->getTrans('watchVideo') ?></a></p>
        <?php endif; ?>
        <?php if ($image) : ?>
            <img class="fx-exercise-image" src="<?=$this->escape($image) ?>" alt="<?=$this->escape($exercise->getTitle()) ?>">
        <?php elseif (!$video) : ?>
            <div class="fx-exercise-image fx-card__placeholder"><i class="fa-solid fa-person-running"></i></div>
        <?php endif; ?>
    </div>
    <div class="fx-split__half">
        <?php if ($exercise->getDescription() !== '') : ?>
            <section class="fx-prose mb-4">
                <?=$this->purify($exercise->getDescription()) ?>
            </section>
        <?php endif; ?>
        <?php if ($exercise->getInstructions() !== '') : ?>
            <section class="fx-card fx-card--padded fx-prose mb-4">
                <h2 class="fx-section-title"><i class="fa-solid fa-list-ol"></i> <?=$this->getTrans('howTo') ?></h2>
                <?=$this->purify($exercise->getInstructions()) ?>
            </section>
        <?php endif; ?>
        <?php if ($exercise->getNotes() !== '') : ?>
            <section class="fx-note">
                <h2 class="fx-note__title"><i class="fa-solid fa-triangle-exclamation"></i> <?=$this->getTrans('notes') ?></h2>
                <p class="mb-0"><?=nl2br($this->escape($exercise->getNotes())) ?></p>
            </section>
        <?php endif; ?>
    </div>
</div>

<p class="mt-4">
    <a href="<?=$this->getUrl(['action' => 'index']) ?>"><i class="fa-solid fa-arrow-left"></i> <?=$this->getTrans('backToLibrary') ?></a>
</p>
