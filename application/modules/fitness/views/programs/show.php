<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\ProgramSession;
use Modules\Fitness\Service\Media;

/** @var \Modules\Fitness\Models\Program $program */
$program = $this->get('program');
/** @var \Modules\Fitness\Models\ProgramPhase[] $phases */
$phases = $this->get('phases');
$image = Media::imageUrl($program->getImage(), BASE_URL);
$price = $program->isPaid() ? $this->getFormattedCurrency((float)$program->getPrice(), $program->getCurrency()) : $this->getTrans('accessFree');
?>
<?php if ($this->get('isPreview')) : ?>
    <div class="alert alert-warning"><i class="fa-solid fa-eye"></i> <?=$this->getTrans('previewProgram') ?></div>
<?php endif; ?>

<section class="fx-program-hero">
    <div class="fx-program-hero__content">
        <?php if ($program->getGoal() !== '') : ?>
            <span class="fx-eyebrow fx-eyebrow--light"><?=$this->escape($program->getGoal()) ?></span>
        <?php endif; ?>
        <h1 class="fx-hero__title"><?=$this->escape($program->getTitle()) ?></h1>
        <?php if ($program->getTeaser() !== '') : ?>
            <p class="fx-hero__text"><?=$this->escape($program->getTeaser()) ?></p>
        <?php endif; ?>
        <ul class="fx-meta fx-meta--light">
            <li><i class="fa-regular fa-calendar"></i> <?=$program->getPhaseCount() ?> <?=$this->getTrans('phases') ?></li>
            <li><i class="fa-solid fa-dumbbell"></i> <?=$program->getSessionCount() ?> <?=$this->getTrans('sessions') ?></li>
            <li><i class="fa-solid fa-signal"></i> <?=$this->getTrans($program->getDifficultyKey()) ?></li>
        </ul>
    </div>
    <?php if ($image) : ?>
        <div class="fx-program-hero__media"><img src="<?=$this->escape($image) ?>" alt=""></div>
    <?php endif; ?>
</section>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if ($program->getDescription() !== '') : ?>
            <section class="fx-card fx-card--padded fx-prose">
                <h2 class="fx-section-title"><?=$this->getTrans('aboutProgram') ?></h2>
                <?=$this->purify($program->getDescription()) ?>
            </section>
        <?php endif; ?>

        <section class="fx-section">
            <h2 class="fx-section-title"><?=$this->getTrans('programPlan') ?></h2>
            <?php if ($phases) : ?>
                <div class="fx-plan">
                    <?php foreach ($phases as $index => $phase) : ?>
                        <?php $sessions = $phase->getSessions(); ?>
                        <details class="fx-plan__phase"<?=$index === 0 ? ' open' : '' ?>>
                            <summary>
                                <span class="fx-plan__title"><?=$this->escape($phase->getTitle()) ?></span>
                                <span class="fx-plan__count"><?=count($sessions) ?> <?=$this->getTrans('sessions') ?></span>
                            </summary>
                            <?php if ($phase->getDescription() !== '') : ?>
                                <p class="fx-plan__description"><?=$this->escape($phase->getDescription()) ?></p>
                            <?php endif; ?>
                            <ul class="fx-plan__sessions">
                                <?php foreach ($sessions as $session) : ?>
                                    <li>
                                        <span class="fx-plan__session-title">
                                            <i class="fa-solid fa-dumbbell"></i>
                                            <?=$this->escape($session->getDisplayTitle()) ?>
                                            <?php if ($session->isOptional()) : ?>
                                                <span class="fx-badge fx-badge--soft"><?=$this->getTrans('optional') ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($session->getDayHint()) : ?>
                                            <span class="fx-plan__day"><?=$this->getTrans(ProgramSession::DAYS[$session->getDayHint()]) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="fx-empty"><?=$this->getTrans('programPlanEmpty') ?></p>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-4">
        <aside class="fx-card fx-card--padded fx-cta">
            <div class="fx-cta__price"><?=$price ?></div>
            <?php if ($program->isPaid()) : ?>
                <p class="fx-cta__note"><?=$this->getTrans('paidProgramNote') ?></p>
            <?php endif; ?>
            <button type="button" class="btn fx-btn fx-btn--primary w-100" disabled>
                <i class="fa-solid fa-play"></i> <?=$this->getTrans('joinProgram') ?>
            </button>
            <p class="fx-cta__note"><?=$this->getTrans('joinComingSoon') ?></p>
            <?php if (!$this->getUser()) : ?>
                <p class="fx-cta__note">
                    <i class="fa-solid fa-circle-info"></i> <?=$this->getTrans('joinNeedsAccount') ?>
                    <a href="<?=$this->getUrl(['module' => 'user', 'controller' => 'login', 'action' => 'index']) ?>"><?=$this->getTrans('login') ?></a>
                </p>
            <?php endif; ?>
        </aside>
    </div>
</div>
