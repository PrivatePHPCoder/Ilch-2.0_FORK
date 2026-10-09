<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\Enrollment;

/** @var array<int, array{enrollment: Enrollment, progress: \Modules\Fitness\Models\Progress, lastActivity: string|null}> $participants */
$participants = $this->get('participants');
/** @var array<int, \Modules\Fitness\Models\Program> $programs */
$programs = $this->get('programs');
/** @var \Ilch\Pagination $pagination */
$pagination = $this->get('pagination');
$programId = (int)$this->get('programId');
$status = (int)$this->get('status');

$badgeClasses = [
    Enrollment::STATUS_ACTIVE => 'bg-success',
    Enrollment::STATUS_PAUSED => 'bg-warning text-dark',
    Enrollment::STATUS_COMPLETED => 'bg-info text-dark',
    Enrollment::STATUS_REVOKED => 'bg-secondary',
];
$filterUrl = array_filter(['action' => 'index', 'program' => $programId, 'status' => $status]);
$formatDate = static fn (?string $date): string => $date ? (new \Ilch\Date($date))->format('d.m.Y H:i', true) : '–';
?>
<h1><?=$this->getTrans('menuParticipants') ?></h1>
<p><?=$this->getTrans('participantsIntro') ?></p>

<form method="GET" action="<?=$this->getUrl(['action' => 'index']) ?>" class="row g-2 align-items-end mb-3" id="participantFilter">
    <div class="col-md-4">
        <label for="filterProgram" class="form-label"><?=$this->getTrans('program') ?></label>
        <select class="form-select" id="filterProgram" data-param="program">
            <option value="0"><?=$this->getTrans('allPrograms') ?></option>
            <?php foreach ($programs as $program) : ?>
                <option value="<?=$program->getId() ?>"<?=$programId === $program->getId() ? ' selected' : '' ?>><?=$this->escape($program->getTitle()) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label for="filterStatus" class="form-label"><?=$this->getTrans('status') ?></label>
        <select class="form-select" id="filterStatus" data-param="status">
            <option value="0"><?=$this->getTrans('allStates') ?></option>
            <?php foreach (Enrollment::ADMIN_STATUSES as $statusValue => $labelKey) : ?>
                <option value="<?=$statusValue ?>"<?=$status === $statusValue ? ' selected' : '' ?>><?=$this->getTrans($labelKey) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-filter"></i> <?=$this->getTrans('filter') ?></button>
    </div>
</form>

<?php if ($participants) : ?>
    <?=$pagination->getHtml($this, $filterUrl) ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle">
            <thead>
                <tr>
                    <th><?=$this->getTrans('participant') ?></th>
                    <th><?=$this->getTrans('program') ?></th>
                    <th><?=$this->getTrans('status') ?></th>
                    <th style="min-width: 10rem;"><?=$this->getTrans('progress') ?></th>
                    <th><?=$this->getTrans('startedAt') ?></th>
                    <th><?=$this->getTrans('lastActivity') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $participant) : ?>
                    <?php
                    $enrollment = $participant['enrollment'];
                    $progress = $participant['progress'];
                    $labelKey = Enrollment::ADMIN_STATUSES[$enrollment->getStatus()];
                    $badgeClass = $badgeClasses[$enrollment->getStatus()];
                    $changes = [];
                    if ($enrollment->getStatus() === Enrollment::STATUS_ACTIVE) {
                        $changes['pause'] = ['participationPause', 'fa-solid fa-pause'];
                    }
                    if (!$enrollment->grantsAccess()) {
                        $changes['reactivate'] = ['participationReactivate', 'fa-solid fa-play'];
                    }
                    if ($enrollment->getStatus() !== Enrollment::STATUS_REVOKED) {
                        $changes['revoke'] = ['participationRevoke', 'fa-solid fa-ban'];
                    }
                    ?>
                    <tr>
                        <td>
                            <?php if ($enrollment->getUserName() !== '') : ?>
                                <a href="<?=$this->getUrl(['module' => 'user', 'controller' => 'profil', 'action' => 'index', 'user' => $enrollment->getUserId()], '') ?>" target="_blank" rel="noopener"><?=$this->escape($enrollment->getUserName()) ?></a>
                            <?php else : ?>
                                #<?=$enrollment->getUserId() ?>
                            <?php endif; ?>
                        </td>
                        <td><a href="<?=$this->getUrl(['action' => 'index', 'program' => $enrollment->getProgramId()]) ?>"><?=$this->escape($enrollment->getProgramTitle()) ?></a></td>
                        <td><span class="badge <?=$badgeClass ?>"><?=$this->getTrans($labelKey) ?></span></td>
                        <td>
                            <div class="progress" style="height: 0.5rem;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?=$progress->getPercent() ?>" aria-label="<?=$this->getTrans('progress') ?>">
                                <div class="progress-bar bg-success" style="width: <?=$progress->getPercent() ?>%"></div>
                            </div>
                            <small class="text-muted"><?=$progress->getPercent() ?> % · <?=$this->getTrans('sessionsDoneOf', $progress->getDone(), $progress->getTotal()) ?></small>
                        </td>
                        <td><?=$formatDate($enrollment->getStartedAt()) ?></td>
                        <td><?=$formatDate($participant['lastActivity']) ?></td>
                        <td class="text-end text-nowrap">
                            <?php foreach ($changes as $change => [$changeKey, $icon]) : ?>
                                <form method="POST" action="<?=$this->getUrl(['action' => 'status']) ?>" class="d-inline">
                                    <?=$this->getTokenField() ?>
                                    <input type="hidden" name="id" value="<?=$enrollment->getId() ?>">
                                    <input type="hidden" name="change" value="<?=$change ?>">
                                    <input type="hidden" name="program" value="<?=$programId ?>">
                                    <input type="hidden" name="status" value="<?=$status ?>">
                                    <input type="hidden" name="page" value="<?=(int)$this->getRequest()->getParam('page') ?>">
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-<?=$change === 'revoke' ? 'danger' : 'secondary' ?>"
                                            title="<?=$this->getTrans($changeKey) ?>"
                                            <?=$change === 'revoke' ? 'data-confirm="' . $this->getTrans('participationRevokeConfirm') . '"' : '' ?>>
                                        <i class="<?=$icon ?>"></i> <?=$this->getTrans($changeKey) ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?=$pagination->getHtml($this, $filterUrl) ?>
<?php else : ?>
    <p><?=$this->getTrans($programId || $status ? 'noParticipantsForFilter' : 'noParticipants') ?></p>
<?php endif; ?>

<script>
    $(function () {
        // Ilch reads parameters from the path, so the filter builds a path instead of a query string.
        $('#participantFilter').on('submit', function (event) {
            event.preventDefault();
            let url = <?=json_encode($this->getUrl(['action' => 'index'])) ?>;
            $(this).find('select[data-param]').each(function () {
                if ($(this).val() !== '0') {
                    url += '/' + $(this).data('param') + '/' + encodeURIComponent($(this).val());
                }
            });
            window.location.href = url;
        });

        $('button[data-confirm]').on('click', function (event) {
            if (!window.confirm($(this).data('confirm'))) {
                event.preventDefault();
            }
        });
    });
</script>
