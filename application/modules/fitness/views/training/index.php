<?php

/** @var \Ilch\View $this */

/** @var array $trainings */
$trainings = $this->get('trainings');
/** @var \Modules\Fitness\Models\Order[] $openOrders */
$openOrders = $this->get('openOrders');
?>
<header class="fx-page-head">
    <h1 class="fx-page-title"><?=$this->getTrans('myTraining') ?></h1>
    <p class="fx-page-lead"><?=$this->getTrans('myTrainingLead') ?></p>
</header>

<?php foreach ($openOrders as $order) : ?>
    <div class="fx-card fx-card--padded fx-pending">
        <i class="fa-solid fa-hourglass-half"></i>
        <div>
            <strong><?=$this->getTrans('paymentPendingFor', $this->escape($order->getProgramTitle())) ?></strong>
            <span class="fx-muted"><?=$this->getTrans('paymentPendingText', $this->escape($order->getReferenceCode())) ?></span>
        </div>
        <a class="btn fx-btn fx-btn--primary" href="<?=$this->getUrl(['controller' => 'orders', 'action' => 'show', 'id' => $order->getId()]) ?>"><?=$this->getTrans('continuePayment') ?></a>
    </div>
<?php endforeach; ?>

<?php if ($trainings) : ?>
    <div class="fx-grid fx-grid--wide">
        <?php foreach ($trainings as $training) : ?>
            <?php $this->load('partials/trainingCard.php', ['training' => $training]); ?>
        <?php endforeach; ?>
    </div>
<?php elseif (!$openOrders) : ?>
    <div class="fx-empty">
        <p><?=$this->getTrans('noTrainingYet') ?></p>
        <a class="btn fx-btn fx-btn--primary" href="<?=$this->getUrl(['controller' => 'programs', 'action' => 'index']) ?>"><?=$this->getTrans('showPrograms') ?></a>
    </div>
<?php endif; ?>

<?php if ($this->get('hasOrders')) : ?>
    <p class="mt-4"><a href="<?=$this->getUrl(['controller' => 'orders', 'action' => 'index']) ?>"><i class="fa-solid fa-receipt"></i> <?=$this->getTrans('myOrders') ?></a></p>
<?php endif; ?>
