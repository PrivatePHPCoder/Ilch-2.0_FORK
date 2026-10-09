<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Milestone[] $milestones */
$milestones = $this->get('milestones') ?: [];
?>
<?php if ($milestones) : ?>
    <section class="fx-celebrate" role="status">
        <div class="fx-celebrate__icon"><i class="<?=$this->escape($milestones[0]->getIcon()) ?>"></i></div>
        <div class="fx-celebrate__body">
            <div class="fx-eyebrow fx-eyebrow--light"><?=$this->getTrans(count($milestones) > 1 ? 'newMilestones' : 'newMilestone') ?></div>
            <?php foreach ($milestones as $milestone) : ?>
                <h2 class="fx-celebrate__title"><?=$this->escape($milestone->getDisplayTitle($this->getTranslator())) ?></h2>
            <?php endforeach; ?>
            <a class="fx-celebrate__link" href="<?=$this->getUrl(['module' => 'fitness', 'controller' => 'milestones', 'action' => 'index']) ?>">
                <?=$this->getTrans('showMyMilestones') ?> <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>
<?php endif; ?>
