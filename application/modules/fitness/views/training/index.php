<?php

/** @var \Ilch\View $this */

/** @var array $trainings */
$trainings = $this->get('trainings');
?>
<header class="fx-page-head">
    <h1 class="fx-page-title"><?=$this->getTrans('myTraining') ?></h1>
    <p class="fx-page-lead"><?=$this->getTrans('myTrainingLead') ?></p>
</header>

<?php if ($trainings) : ?>
    <div class="fx-grid fx-grid--wide">
        <?php foreach ($trainings as $training) : ?>
            <?php $this->load('partials/trainingCard.php', ['training' => $training]); ?>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="fx-empty">
        <p><?=$this->getTrans('noTrainingYet') ?></p>
        <a class="btn fx-btn fx-btn--primary" href="<?=$this->getUrl(['controller' => 'programs', 'action' => 'index']) ?>"><?=$this->getTrans('showPrograms') ?></a>
    </div>
<?php endif; ?>
