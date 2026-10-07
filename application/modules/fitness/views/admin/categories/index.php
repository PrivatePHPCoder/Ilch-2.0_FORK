<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Category[] $categories */
$categories = $this->get('categories');
?>
<h1>
    <?=$this->getTrans('menuCategories') ?>
    <a class="badge rounded-pill bg-secondary" href="<?=$this->getUrl(['action' => 'treat']) ?>" title="<?=$this->getTrans('add') ?>"><i class="fa-solid fa-plus"></i></a>
</h1>
<?php if ($categories) : ?>
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
                </colgroup>
                <thead>
                    <tr>
                        <th><?=$this->getCheckAllCheckbox('check_categories') ?></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th><?=$this->getTrans('name') ?></th>
                        <th><?=$this->getTrans('exercises') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category) : ?>
                        <tr>
                            <td>
                                <input type="hidden" name="items[]" value="<?=$category->getId() ?>">
                                <?=$this->getDeleteCheckbox('check_categories', $category->getId()) ?>
                            </td>
                            <td><?=$this->getEditIcon(['action' => 'treat', 'id' => $category->getId()]) ?></td>
                            <td><?=$this->getDeleteIcon(['action' => 'del', 'id' => $category->getId()]) ?></td>
                            <td><i class="fa-solid fa-sort"></i></td>
                            <td><?=$this->escape($category->getName()) ?></td>
                            <td><?=$category->getExerciseCount() ?></td>
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
                ui.placeholder.html("<td colspan='6'></td>");
                ui.placeholder.height(ui.item.height());
            }
        }).disableSelection();
    </script>
<?php else : ?>
    <p><?=$this->getTrans('noCategories') ?></p>
<?php endif; ?>
