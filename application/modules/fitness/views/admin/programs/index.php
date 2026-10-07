<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Program[] $programs */
$programs = $this->get('programs');
$statusClasses = [0 => 'bg-secondary', 1 => 'bg-success', 2 => 'bg-dark'];
?>
<h1>
    <?=$this->getTrans('menuPrograms') ?>
    <a class="badge rounded-pill bg-secondary" href="<?=$this->getUrl(['action' => 'treat']) ?>" title="<?=$this->getTrans('add') ?>"><i class="fa-solid fa-plus"></i></a>
</h1>
<?php if ($programs) : ?>
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
                        <th><?=$this->getCheckAllCheckbox('check_programs') ?></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th><?=$this->getTrans('title') ?></th>
                        <th><?=$this->getTrans('status') ?></th>
                        <th><?=$this->getTrans('accessType') ?></th>
                        <th><?=$this->getTrans('phases') ?></th>
                        <th><?=$this->getTrans('sessions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($programs as $program) : ?>
                        <tr>
                            <td>
                                <input type="hidden" name="items[]" value="<?=$program->getId() ?>">
                                <?=$this->getDeleteCheckbox('check_programs', $program->getId()) ?>
                            </td>
                            <td><?=$this->getEditIcon(['action' => 'treat', 'id' => $program->getId()]) ?></td>
                            <td>
                                <a href="<?=$this->getUrl(['action' => 'structure', 'id' => $program->getId()]) ?>" title="<?=$this->getTrans('programStructure') ?>">
                                    <span class="fa-solid fa-calendar-week text-info"></span>
                                </a>
                            </td>
                            <td><?=$this->getDeleteIcon(['action' => 'del', 'id' => $program->getId()]) ?></td>
                            <td><i class="fa-solid fa-sort"></i></td>
                            <td><?=$this->escape($program->getTitle()) ?></td>
                            <td><span class="badge <?=$statusClasses[$program->getStatus()] ?>"><?=$this->getTrans($program->getStatusKey()) ?></span></td>
                            <td>
                                <?php if ($program->isPaid()) : ?>
                                    <?=$this->getFormattedCurrency((float)$program->getPrice(), $program->getCurrency()) ?>
                                <?php else : ?>
                                    <?=$this->getTrans('accessFree') ?>
                                <?php endif; ?>
                            </td>
                            <td><?=$program->getPhaseCount() ?></td>
                            <td><?=$program->getSessionCount() ?></td>
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
    <p><?=$this->getTrans('noPrograms') ?></p>
<?php endif; ?>
