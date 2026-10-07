<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Program[] $programs */
$programs = $this->get('programs');
?>
<header class="fx-page-head">
    <h1 class="fx-page-title"><?=$this->getTrans('menuPrograms') ?></h1>
    <p class="fx-page-lead"><?=$this->getTrans('programsLead') ?></p>
</header>

<?php if ($programs) : ?>
    <div class="fx-grid">
        <?php foreach ($programs as $program) : ?>
            <?php $this->load('partials/programCard.php', ['program' => $program]); ?>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <p class="fx-empty"><?=$this->getTrans('noProgramsYet') ?></p>
<?php endif; ?>
