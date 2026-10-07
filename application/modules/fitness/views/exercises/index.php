<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Service\Media;

/** @var \Modules\Fitness\Models\Exercise[] $exercises */
$exercises = $this->get('exercises');
/** @var \Modules\Fitness\Models\Category[] $categories */
$categories = $this->get('categories');
/** @var array<int, \Modules\Fitness\Models\MuscleGroup> $muscleGroups */
$muscleGroups = $this->get('muscleGroups');
/** @var array<int, \Modules\Fitness\Models\MuscleGroup> $filterMuscleGroups */
$filterMuscleGroups = $this->get('filterMuscleGroups');
$categoryId = (int)$this->get('categoryId');
$muscleGroupId = (int)$this->get('muscleGroupId');

$filterUrl = function (int $category, int $muscle): string {
    return $this->getUrl(array_merge(
        ['action' => 'index'],
        $category ? ['category' => $category] : [],
        $muscle ? ['muscle' => $muscle] : []
    ));
};
?>
<header class="fx-page-head">
    <h1 class="fx-page-title"><?=$this->getTrans('exerciseLibrary') ?></h1>
    <p class="fx-page-lead"><?=$this->getTrans('exerciseLibraryLead') ?></p>
</header>

<?php if ($categories || $filterMuscleGroups) : ?>
    <nav class="fx-filters" aria-label="<?=$this->getTrans('filter') ?>">
        <?php if ($categories) : ?>
            <div class="fx-chips">
                <a class="fx-chip<?=$categoryId === 0 ? ' is-active' : '' ?>" href="<?=$filterUrl(0, $muscleGroupId) ?>"><?=$this->getTrans('allCategories') ?></a>
                <?php foreach ($categories as $category) : ?>
                    <a class="fx-chip<?=$categoryId === $category->getId() ? ' is-active' : '' ?>" href="<?=$filterUrl($category->getId(), $muscleGroupId) ?>"><?=$this->escape($category->getName()) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($filterMuscleGroups) : ?>
            <div class="fx-chips">
                <a class="fx-chip<?=$muscleGroupId === 0 ? ' is-active' : '' ?>" href="<?=$filterUrl($categoryId, 0) ?>"><?=$this->getTrans('allMuscleGroups') ?></a>
                <?php foreach ($filterMuscleGroups as $muscleGroup) : ?>
                    <a class="fx-chip<?=$muscleGroupId === $muscleGroup->getId() ? ' is-active' : '' ?>" href="<?=$filterUrl($categoryId, $muscleGroup->getId()) ?>"><?=$this->escape($muscleGroup->getName()) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<?php if ($exercises) : ?>
    <div class="fx-grid">
        <?php foreach ($exercises as $exercise) : ?>
            <?php
            $url = $this->getUrl(['action' => 'show', 'id' => $exercise->getId()]);
            $image = Media::imageUrl($exercise->getImage(), BASE_URL);
            $primary = $muscleGroups[$exercise->getPrimaryMuscleGroupId()] ?? null;
            ?>
            <article class="fx-card fx-exercise-card">
                <a class="fx-card__media" href="<?=$url ?>" tabindex="-1" aria-hidden="true">
                    <?php if ($image) : ?>
                        <img src="<?=$this->escape($image) ?>" alt="" loading="lazy">
                    <?php else : ?>
                        <span class="fx-card__placeholder"><i class="fa-solid fa-person-running"></i></span>
                    <?php endif; ?>
                    <?php if ($exercise->getVideoUrl() !== '') : ?>
                        <span class="fx-badge fx-badge--video"><i class="fa-solid fa-circle-play"></i> <?=$this->getTrans('video') ?></span>
                    <?php endif; ?>
                </a>
                <div class="fx-card__body">
                    <?php if ($exercise->getCategoryName() !== '') : ?>
                        <div class="fx-eyebrow"><?=$this->escape($exercise->getCategoryName()) ?></div>
                    <?php endif; ?>
                    <h3 class="fx-card__title"><a href="<?=$url ?>"><?=$this->escape($exercise->getTitle()) ?></a></h3>
                    <ul class="fx-meta">
                        <?php if ($primary) : ?>
                            <li><i class="fa-solid fa-bullseye"></i> <?=$this->escape($primary->getName()) ?></li>
                        <?php endif; ?>
                        <li><i class="fa-solid fa-signal"></i> <?=$this->getTrans($exercise->getDifficultyKey()) ?></li>
                    </ul>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <p class="fx-empty"><?=$this->getTrans($categoryId || $muscleGroupId ? 'noExercisesForFilter' : 'noExercisesYet') ?></p>
<?php endif; ?>
