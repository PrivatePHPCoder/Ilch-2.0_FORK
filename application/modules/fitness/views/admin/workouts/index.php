<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Workout[] $workouts */
$workouts = $this->get('workouts');
?>
<?php $this->load('admin/partials/listHead.php', ['title' => 'menuWorkouts', 'addLabel' => 'addWorkout']); ?>
<?php if ($workouts) : ?>
    <form method="POST">
        <?=$this->getTokenField() ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <colgroup>
                    <col class="icon_width">
                    <col class="icon_width">
                    <col class="icon_width">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col class="icon_width">
                </colgroup>
                <thead>
                    <tr>
                        <th><?=$this->getCheckAllCheckbox('check_workouts') ?></th>
                        <th></th>
                        <th></th>
                        <th><?=$this->getTrans('title') ?></th>
                        <th><?=$this->getTrans('exercises') ?></th>
                        <th><?=$this->getTrans('durationMin') ?></th>
                        <th><?=$this->getTrans('difficulty') ?></th>
                        <th title="<?=$this->getTrans('active') ?>"><i class="fa-solid fa-power-off"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workouts as $workout) : ?>
                        <tr>
                            <td><?=$this->getDeleteCheckbox('check_workouts', $workout->getId()) ?></td>
                            <td><?=$this->getEditIcon(['action' => 'treat', 'id' => $workout->getId()]) ?></td>
                            <td><?=$this->getDeleteIcon(['action' => 'del', 'id' => $workout->getId()]) ?></td>
                            <td><?=$this->escape($workout->getTitle()) ?></td>
                            <td><?=$workout->getExerciseCount() ?></td>
                            <td><?=$workout->getDurationMin() !== null ? $workout->getDurationMin() . ' ' . $this->getTrans('minutesShort') : '' ?></td>
                            <td><?=$this->getTrans($workout->getDifficultyKey()) ?></td>
                            <td><i class="fa-<?=$workout->isActive() ? 'solid fa-square-check text-success' : 'regular fa-square text-muted' ?>"></i></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?=$this->getListBar(['delete' => 'delete']) ?>
    </form>
<?php else : ?>
    <p><?=$this->getTrans('noWorkouts') ?></p>
<?php endif; ?>
