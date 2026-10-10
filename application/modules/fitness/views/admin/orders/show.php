<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\Enrollment;
use Modules\Fitness\Models\Order;

/** @var Order $order */
$order = $this->get('order');
/** @var Enrollment|null $enrollment */
$enrollment = $this->get('enrollment');

$badgeClasses = [
    Order::STATUS_OPEN => 'bg-warning text-dark',
    Order::STATUS_PAID => 'bg-success',
    Order::STATUS_CANCELLED => 'bg-secondary',
    Order::STATUS_REFUNDED => 'bg-dark',
];
$formatDate = static fn (?string $date): string => $date ? (new \Ilch\Date($date))->format('d.m.Y H:i', true) : '–';
$canConfirm = $order->getUserId() !== null && in_array($order->getStatus(), [Order::STATUS_OPEN, Order::STATUS_CANCELLED], true);
?>
<h1>
    <?=$this->getTrans('orderNumber', $this->escape($order->getReferenceCode())) ?>
    <span class="badge <?=$badgeClasses[$order->getStatus()] ?>"><?=$this->getTrans($order->getStatusKey()) ?></span>
</h1>

<div class="row">
    <div class="col-xl-6">
        <table class="table">
            <tbody>
                <tr>
                    <th><?=$this->getTrans('program') ?></th>
                    <td><a href="<?=$this->getUrl(['controller' => 'programs', 'action' => 'treat', 'id' => $order->getProgramId()]) ?>"><?=$this->escape($order->getProgramTitle()) ?></a></td>
                </tr>
                <tr>
                    <th><?=$this->getTrans('participant') ?></th>
                    <td>
                        <?php if ($order->getUserId() !== null) : ?>
                            <a href="<?=$this->getUrl(['module' => 'user', 'controller' => 'profil', 'action' => 'index', 'user' => $order->getUserId()], '') ?>" target="_blank" rel="noopener"><?=$this->escape($order->getUserName()) ?></a>
                            <br><a href="mailto:<?=$this->escape($order->getUserEmail()) ?>"><?=$this->escape($order->getUserEmail()) ?></a>
                        <?php else : ?>
                            <span class="text-muted"><?=$this->getTrans('deletedUser') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?=$this->getTrans('amount') ?></th>
                    <td><strong><?=$this->getFormattedCurrency((float)$order->getAmount(), $order->getCurrency()) ?></strong></td>
                </tr>
                <tr>
                    <th><?=$this->getTrans('paymentReference') ?></th>
                    <td><code><?=$this->escape($order->getReferenceCode()) ?></code></td>
                </tr>
                <tr>
                    <th><?=$this->getTrans('orderDate') ?></th>
                    <td><?=$formatDate($order->getCreatedAt()) ?></td>
                </tr>
                <?php if ($order->getPaidAt()) : ?>
                    <tr>
                        <th><?=$this->getTrans('paidAt') ?></th>
                        <td>
                            <?=$formatDate($order->getPaidAt()) ?>
                            <?php if ($order->getPaymentMethodKey() !== '') : ?>
                                · <?=$this->getTrans($order->getPaymentMethodKey()) ?>
                            <?php endif; ?>
                            <?php if ($this->get('confirmedByName') !== '') : ?>
                                · <?=$this->getTrans('confirmedBy', $this->escape($this->get('confirmedByName'))) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if ($order->getProviderOrderId() !== null) : ?>
                    <tr>
                        <th><?=$this->getTrans('paymentPaypalCheckout') ?></th>
                        <td>
                            <?=$this->getTrans('paypalOrderId') ?>: <code><?=$this->escape($order->getProviderOrderId()) ?></code>
                            <?php if ($order->getProviderCaptureId() !== null) : ?>
                                <br><?=$this->getTrans('paypalCaptureId') ?>: <code><?=$this->escape($order->getProviderCaptureId()) ?></code>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th><?=$this->getTrans('participation') ?></th>
                    <td>
                        <?php if ($enrollment) : ?>
                            <a href="<?=$this->getUrl(['controller' => 'participants', 'action' => 'index', 'program' => $order->getProgramId()]) ?>"><?=$this->getTrans(Enrollment::ADMIN_STATUSES[$enrollment->getStatus()]) ?></a>
                        <?php else : ?>
                            <span class="text-muted"><?=$this->getTrans('noParticipationYet') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="col-xl-6">
        <form method="POST" action="<?=$this->getUrl(['action' => 'status']) ?>" id="orderForm">
            <?=$this->getTokenField() ?>
            <input type="hidden" name="id" value="<?=$order->getId() ?>">
            <div class="mb-3">
                <label for="note" class="form-label"><?=$this->getTrans('internalNote') ?></label>
                <textarea class="form-control" id="note" name="note" rows="3" maxlength="2000"><?=$this->escape($order->getNote()) ?></textarea>
                <div class="form-text"><?=$this->getTrans('internalNoteInfo') ?></div>
            </div>

            <?php if ($canConfirm) : ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h2 class="h5"><?=$this->getTrans('confirmPayment') ?></h2>
                        <p class="text-muted"><?=$this->getTrans('confirmPaymentInfo') ?></p>
                        <div class="mb-3">
                            <label for="method" class="form-label"><?=$this->getTrans('paymentMethod') ?></label>
                            <select class="form-select" id="method" name="method">
                                <?php foreach (Order::METHODS as $methodKey => $methodName) : ?>
                                    <option value="<?=$methodKey ?>"><?=$this->getTrans($methodName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success" name="change" value="confirm" data-confirm="<?=$this->getTrans('confirmPaymentConfirm') ?>">
                            <i class="fa-solid fa-check"></i> <?=$this->getTrans('confirmPayment') ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-outline-secondary" name="change" value="note">
                    <i class="fa-solid fa-floppy-disk"></i> <?=$this->getTrans('saveNote') ?>
                </button>
                <?php if ($order->isOpen() && $order->getProviderOrderId() !== null) : ?>
                    <button type="submit" class="btn btn-outline-primary" name="change" value="paypalsync">
                        <i class="fa-brands fa-paypal"></i> <?=$this->getTrans('paypalSync') ?>
                    </button>
                <?php endif; ?>
                <?php if ($order->isOpen()) : ?>
                    <button type="submit" class="btn btn-outline-danger" name="change" value="cancel" data-confirm="<?=$this->getTrans('cancelOrderAdminConfirm') ?>">
                        <i class="fa-solid fa-xmark"></i> <?=$this->getTrans('cancelOrder') ?>
                    </button>
                <?php endif; ?>
                <?php if ($order->isPaid()) : ?>
                    <button type="submit" class="btn btn-outline-danger" name="change" value="refund" data-confirm="<?=$this->getTrans('refundOrderConfirm') ?>">
                        <i class="fa-solid fa-rotate-left"></i> <?=$this->getTrans('refundOrder') ?>
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<p class="mt-3"><a href="<?=$this->getUrl(['action' => 'index']) ?>"><i class="fa-solid fa-arrow-left"></i> <?=$this->getTrans('menuOrders') ?></a></p>

<script>
    $(function () {
        $('#orderForm button[data-confirm]').on('click', function (event) {
            if (!window.confirm($(this).data('confirm'))) {
                event.preventDefault();
            }
        });
    });
</script>
