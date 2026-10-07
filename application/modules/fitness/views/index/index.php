<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Program[] $programs */
$programs = $this->get('programs');
/** @var \Modules\User\Models\User|null $user */
$user = $this->get('user');
?>
<section class="fx-hero">
    <div class="fx-hero__content">
        <span class="fx-eyebrow fx-eyebrow--light"><?=$this->getTrans('menuFitness') ?></span>
        <h1 class="fx-hero__title">
            <?=$user ? $this->getTrans('helloUser', $this->escape($user->getName())) : $this->getTrans('welcomeTitle') ?>
        </h1>
        <p class="fx-hero__text"><?=$this->getTrans('welcomeText') ?></p>
        <div class="fx-hero__actions">
            <a class="btn fx-btn fx-btn--primary" href="<?=$this->getUrl(['controller' => 'programs', 'action' => 'index']) ?>">
                <i class="fa-solid fa-calendar-week"></i> <?=$this->getTrans('showPrograms') ?>
            </a>
            <a class="btn fx-btn fx-btn--ghost" href="<?=$this->getUrl(['controller' => 'exercises', 'action' => 'index']) ?>">
                <i class="fa-solid fa-person-running"></i> <?=$this->getTrans('exerciseLibrary') ?>
            </a>
        </div>
    </div>
    <div class="fx-hero__stats">
        <div class="fx-stat">
            <strong><?=(int)$this->get('programCount') ?></strong>
            <span><?=$this->getTrans('menuPrograms') ?></span>
        </div>
        <div class="fx-stat">
            <strong><?=(int)$this->get('exerciseCount') ?></strong>
            <span><?=$this->getTrans('menuExercises') ?></span>
        </div>
    </div>
</section>

<?php if ($user) : ?>
    <section class="fx-card fx-progress-teaser">
        <div class="fx-progress-teaser__icon"><i class="fa-solid fa-chart-line"></i></div>
        <div>
            <h2 class="fx-section-title"><?=$this->getTrans('myProgress') ?></h2>
            <p class="mb-0"><?=$this->getTrans('myProgressEmpty') ?></p>
        </div>
    </section>
<?php endif; ?>

<section class="fx-section">
    <div class="fx-section-head">
        <h2 class="fx-section-title"><?=$this->getTrans('currentPrograms') ?></h2>
        <?php if ($programs) : ?>
            <a href="<?=$this->getUrl(['controller' => 'programs', 'action' => 'index']) ?>"><?=$this->getTrans('allPrograms') ?> <i class="fa-solid fa-arrow-right"></i></a>
        <?php endif; ?>
    </div>
    <?php if ($programs) : ?>
        <div class="fx-grid">
            <?php foreach ($programs as $program) : ?>
                <?php $this->load('partials/programCard.php', ['program' => $program]); ?>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="fx-empty"><?=$this->getTrans('noProgramsYet') ?></p>
    <?php endif; ?>
</section>
