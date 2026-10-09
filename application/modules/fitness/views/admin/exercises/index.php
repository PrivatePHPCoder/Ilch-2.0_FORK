<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Exercise[] $exercises */
$exercises = $this->get('exercises');
/** @var array<int, string> $muscleGroupNames */
$muscleGroupNames = $this->get('muscleGroupNames');
?>
<?php $this->load('admin/partials/listHead.php', ['title' => 'menuExercises', 'addLabel' => 'addExercise']); ?>
<?php if ($exercises) : ?>
    <p class="text-muted"><?=$this->getTrans('sortInfo') ?></p>
    <form method="POST">
        <?=$this->getTokenField() ?>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <colgroup>
                    <col class="icon_width">
                    <col class="icon_width">
                    <col class="icon_width">
                    <col class="icon_width">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col class="icon_width">
                    <col class="icon_width">
                </colgroup>
                <thead>
                    <tr>
                        <th><?=$this->getCheckAllCheckbox('check_exercises') ?></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th><?=$this->getTrans('title') ?></th>
                        <th><?=$this->getTrans('categoryId') ?></th>
                        <th><?=$this->getTrans('menuMuscleGroups') ?></th>
                        <th><?=$this->getTrans('difficulty') ?></th>
                        <th title="<?=$this->getTrans('isPublic') ?>"><i class="fa-solid fa-globe"></i></th>
                        <th title="<?=$this->getTrans('active') ?>"><i class="fa-solid fa-power-off"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exercises as $exercise) : ?>
                        <?php
                        $muscles = [];
                        foreach ($exercise->getMuscleGroupIds() as $muscleGroupId) {
                            if (isset($muscleGroupNames[$muscleGroupId])) {
                                $name = $this->escape($muscleGroupNames[$muscleGroupId]);
                                $muscles[] = $muscleGroupId === $exercise->getPrimaryMuscleGroupId() ? '<strong>' . $name . '</strong>' : $name;
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <input type="hidden" name="items[]" value="<?=$exercise->getId() ?>">
                                <?=$this->getDeleteCheckbox('check_exercises', $exercise->getId()) ?>
                            </td>
                            <td><?=$this->getEditIcon(['action' => 'treat', 'id' => $exercise->getId()]) ?></td>
                            <td><?=$this->getDeleteIcon(['action' => 'del', 'id' => $exercise->getId()]) ?></td>
                            <td><i class="fa-solid fa-sort"></i></td>
                            <td><?=$this->escape($exercise->getTitle()) ?><?php if (in_array($exercise->getId(), $this->get('sampleIds'), true)) : ?> <span class="badge bg-info text-dark"><?=$this->getTrans('sampleBadge') ?></span><?php endif; ?></td>
                            <td><?=$this->escape($exercise->getCategoryName()) ?></td>
                            <td><?=implode(', ', $muscles) ?></td>
                            <td><?=$this->getTrans($exercise->getDifficultyKey()) ?></td>
                            <td><i class="fa-<?=$exercise->isPublic() ? 'solid fa-square-check text-info' : 'regular fa-square text-muted' ?>"></i></td>
                            <td><i class="fa-<?=$exercise->isActive() ? 'solid fa-square-check text-success' : 'regular fa-square text-muted' ?>"></i></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="content_savebox">
            <input type="hidden" class="content_savebox_hidden" name="action" value="">
            <div class="btn-group dropup">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <?=$this->getTrans('selected') ?>
                </button>
                <ul class="dropdown-menu listChooser" role="menu">
                    <li><a class="dropdown-item" href="#" data-hiddenkey="delete"><?=$this->getTrans('delete') ?></a></li>
                </ul>
            </div>
            <button type="submit" class="save_button btn btn-outline-secondary" name="saveOrder" value="save">
                <?=$this->getTrans('saveOrder') ?>
            </button>
        </div>
    </form>
    <script>
        $('table tbody').sortable({
            handle: 'td',
            cursorAt: { left: 15 },
            placeholder: 'table-sort-drop',
            forcePlaceholderSize: true,
            'start': function (event, ui) {
                ui.placeholder.html("<td colspan='10'></td>");
                ui.placeholder.height(ui.item.height());
            }
        }).disableSelection();
    </script>
<?php else : ?>
    <p><?=$this->getTrans('noExercises') ?></p>
<?php endif; ?>
