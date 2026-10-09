<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\ProgramSession;
use Modules\Fitness\Service\Media;

/** @var \Modules\Fitness\Models\Program $program */
$program = $this->get('program');
/** @var \Modules\Fitness\Models\ProgramPhase[] $phases */
$phases = $this->get('phases');
/** @var \Modules\Fitness\Models\Enrollment|null $enrollment */
$enrollment = $this->get('enrollment');
/** @var \Modules\Fitness\Models\Progress|null $progress */
$progress = $this->get('progress');
$canViewContent = (bool)$this->get('canViewContent');
/** @var \Modules\Fitness\Models\Order|null $openOrder */
$openOrder = $this->get('openOrder');
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

<div class="fx-split">
    <div class="fx-split__main">
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
                                <span class="fx-plan__title">
                                    <?php if ($progress && $progress->isPhaseDone($phase->getId())) : ?>
                                        <i class="fa-solid fa-circle-check fx-done-icon" title="<?=$this->getTrans('phaseDone') ?>"></i>
                                    <?php endif; ?>
                                    <?=$this->escape($phase->getTitle()) ?>
                                </span>
                                <span class="fx-plan__count"><?=count($sessions) ?> <?=$this->getTrans('sessions') ?></span>
                            </summary>
                            <?php if ($phase->getDescription() !== '') : ?>
                                <p class="fx-plan__description"><?=$this->escape($phase->getDescription()) ?></p>
                            <?php endif; ?>
                            <ul class="fx-plan__sessions">
                                <?php foreach ($sessions as $session) : ?>
                                    <?php $isDone = $progress && $progress->isSessionDone($session->getId()); ?>
                                    <li class="<?=$isDone ? 'is-done' : '' ?>">
                                        <span class="fx-plan__session-title">
                                            <i class="fa-solid <?=$isDone ? 'fa-circle-check' : 'fa-dumbbell' ?>"></i>
                                            <?php if ($canViewContent) : ?>
                                                <a href="<?=$this->getUrl(['controller' => 'training', 'action' => 'session', 'id' => $session->getId()]) ?>"><?=$this->escape($session->getDisplayTitle()) ?></a>
                                            <?php else : ?>
                                                <?=$this->escape($session->getDisplayTitle()) ?>
                                            <?php endif; ?>
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

    <div class="fx-split__side">
        <aside class="fx-card fx-card--padded fx-cta">
            <?php if ($enrollment && $progress) : ?>
                <div class="fx-eyebrow"><?=$this->getTrans($enrollment->getStatusKey()) ?></div>
                <div class="fx-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?=$progress->getPercent() ?>" aria-label="<?=$this->getTrans('progress') ?>">
                    <div class="fx-progress__bar" style="width: <?=$progress->getPercent() ?>%"></div>
                </div>
                <div class="fx-progress__legend">
                    <strong><?=$progress->getPercent() ?> %</strong>
                    <span><?=$this->getTrans('sessionsDoneOf', $progress->getDone(), $progress->getTotal()) ?></span>
                </div>
                <?php if ($enrollment->grantsAccess() && $progress->getNextSession()) : ?>
                    <p class="fx-cta__note"><?=$this->getTrans('nextSession') ?>: <?=$this->escape($progress->getNextSession()->getDisplayTitle()) ?></p>
                    <a class="btn fx-btn fx-btn--primary w-100 mt-2" href="<?=$this->getUrl(['controller' => 'training', 'action' => 'session', 'id' => $progress->getNextSession()->getId()]) ?>">
                        <i class="fa-solid fa-play"></i> <?=$this->getTrans($progress->getDone() > 0 ? 'continueTraining' : 'startTraining') ?>
                    </a>
                <?php elseif ($progress->isComplete()) : ?>
                    <p class="fx-cta__note"><i class="fa-solid fa-trophy"></i> <?=$this->getTrans('programDoneText') ?></p>
                <?php elseif (!$enrollment->grantsAccess()) : ?>
                    <p class="fx-cta__note"><?=$this->getTrans('enrollmentNoAccess') ?></p>
                <?php endif; ?>
            <?php else : ?>
                <div class="fx-cta__price"><?=$price ?></div>
                <?php if ($program->isPaid()) : ?>
                    <p class="fx-cta__note"><?=$this->getTrans('paidProgramNote') ?></p>
                    <?php if ($openOrder) : ?>
                        <a class="btn fx-btn fx-btn--primary w-100" href="<?=$this->getUrl(['controller' => 'orders', 'action' => 'show', 'id' => $openOrder->getId()]) ?>">
                            <i class="fa-solid fa-receipt"></i> <?=$this->getTrans('continuePayment') ?>
                        </a>
                        <p class="fx-cta__note"><?=$this->getTrans('orderPendingNote', $this->escape($openOrder->getReferenceCode())) ?></p>
                    <?php elseif ($this->get('canBuy') && $this->get('paymentAvailable')) : ?>
                        <form method="POST" action="<?=$this->getUrl(['controller' => 'orders', 'action' => 'create', 'program' => $program->getId()]) ?>">
                            <?=$this->getTokenField() ?>
                            <button type="submit" class="btn fx-btn fx-btn--primary w-100">
                                <i class="fa-solid fa-cart-shopping"></i> <?=$this->getTrans('buyProgram') ?>
                            </button>
                        </form>
                        <p class="fx-cta__note"><?=$this->getTrans('buyNote') ?></p>
                    <?php elseif ($this->get('canBuy')) : ?>
                        <p class="fx-cta__note"><?=$this->getTrans('buyNotAvailable') ?></p>
                    <?php endif; ?>
                <?php elseif ($this->get('canJoin')) : ?>
                    <form method="POST" action="<?=$this->getUrl(['action' => 'join', 'id' => $program->getId()]) ?>">
                        <?=$this->getTokenField() ?>
                        <button type="submit" class="btn fx-btn fx-btn--primary w-100">
                            <i class="fa-solid fa-play"></i> <?=$this->getTrans('joinProgram') ?>
                        </button>
                    </form>
                    <p class="fx-cta__note"><?=$this->getTrans('joinFreeNote') ?></p>
                <?php endif; ?>
                <?php if (!$this->getUser()) : ?>
                    <p class="fx-cta__note">
                        <i class="fa-solid fa-circle-info"></i> <?=$this->getTrans('joinNeedsAccount') ?>
                        <a href="<?=$this->getUrl(['module' => 'user', 'controller' => 'login', 'action' => 'index']) ?>"><?=$this->getTrans('login') ?></a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </aside>
    </div>
</div>
