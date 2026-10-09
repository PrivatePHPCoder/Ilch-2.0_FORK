<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Milestone[] $milestones */
$milestones = $this->get('milestones');
?>
<?php $this->load('admin/partials/listHead.php', ['title' => 'menuMilestones', 'addLabel' => 'addMilestone']); ?>
<p><?=$this->getTrans('milestonesIntro') ?></p>
<?php if ($milestones) : ?>
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
                    <col class="icon_width">
                    <col>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th><?=$this->getCheckAllCheckbox('check_milestones') ?></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th><?=$this->getTrans('title') ?></th>
                        <th><?=$this->getTrans('milestoneCondition') ?></th>
                        <th><?=$this->getTrans('milestoneScope') ?></th>
                        <th><?=$this->getTrans('status') ?></th>
                        <th><?=$this->getTrans('milestoneAchievedCount') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($milestones as $milestone) : ?>
                        <tr>
                            <td>
                                <input type="hidden" name="items[]" value="<?=$milestone->getId() ?>">
                                <?=$this->getDeleteCheckbox('check_milestones', $milestone->getId()) ?>
                            </td>
                            <td><?=$this->getEditIcon(['action' => 'treat', 'id' => $milestone->getId()]) ?></td>
                            <td><?=$this->getDeleteIcon(['action' => 'del', 'id' => $milestone->getId()]) ?></td>
                            <td><i class="fa-solid fa-sort"></i></td>
                            <td><i class="<?=$this->escape($milestone->getIcon()) ?> text-warning"></i></td>
                            <td>
                                <?=$this->escape($milestone->getDisplayTitle($this->getTranslator())) ?>
                                <?php if ($milestone->getTitle() === '') : ?>
                                    <small class="text-muted">(<?=$this->getTrans('milestoneAutoTitle') ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td><?=$this->getTrans($milestone->getConditionKey(), $milestone->getThreshold()) ?></td>
                            <td><?=$milestone->isForAllPrograms() ? $this->getTrans('milestoneAllPrograms') : $this->escape($milestone->getProgramTitle()) ?></td>
                            <td>
                                <?php if ($milestone->isActive()) : ?>
                                    <span class="badge bg-success"><?=$this->getTrans('active') ?></span>
                                <?php else : ?>
                                    <span class="badge bg-secondary"><?=$this->getTrans('inactive') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?=$milestone->getAchievedCount() ?></td>
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
    <p><?=$this->getTrans('noMilestones') ?></p>
    <form method="POST">
        <?=$this->getTokenField() ?>
        <button type="submit" class="btn btn-outline-secondary" name="createDefaults" value="1">
            <i class="fa-solid fa-wand-magic-sparkles"></i> <?=$this->getTrans('milestoneCreateDefaults') ?>
        </button>
    </form>
<?php endif; ?>
