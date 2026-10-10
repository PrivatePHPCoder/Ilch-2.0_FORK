<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\Order;

/** @var Order $order */
$order = $this->get('order');
/** @var \Modules\Fitness\Models\PaymentOptions $payment */
$payment = $this->get('payment');
$amount = $this->getFormattedCurrency((float)$order->getAmount(), $order->getCurrency());
?>
<?php $this->load('partials/visitorBar.php', ['active' => $this->get('visitorView')]); ?>
<header class="fx-page-head">
    <div class="fx-eyebrow"><?=$this->getTrans('orderNumber', $this->escape($order->getReferenceCode())) ?></div>
    <h1 class="fx-page-title">
        <?=$this->escape($order->getProgramTitle()) ?>
        <span class="fx-order-status fx-order-status--<?=$order->getStatus() ?>"><?=$this->getTrans($order->getStatusKey()) ?></span>
    </h1>
    <ul class="fx-meta">
        <li><i class="fa-regular fa-calendar"></i> <?=$this->getTrans('orderedOn', (new \Ilch\Date($order->getCreatedAt()))->format('d.m.Y H:i', true)) ?></li>
        <li><i class="fa-solid fa-receipt"></i> <?=$amount ?></li>
    </ul>
</header>

<?php if ($order->isOpen()) : ?>
    <div class="fx-split">
        <div class="fx-split__main">
            <section class="fx-card fx-card--padded fx-pay">
                <h2 class="fx-section-title"><?=$this->getTrans('howToPay') ?></h2>
                <div class="fx-pay__summary">
                    <div>
                        <span class="fx-muted"><?=$this->getTrans('amountToPay') ?></span>
                        <strong class="fx-pay__amount"><?=$amount ?></strong>
                    </div>
                    <div>
                        <span class="fx-muted"><?=$this->getTrans('paymentReference') ?></span>
                        <strong class="fx-pay__reference">
                            <code><?=$this->escape($order->getReferenceCode()) ?></code>
                            <button type="button" class="btn btn-sm fx-copy" data-fx-copy="<?=$this->escape($order->getReferenceCode()) ?>" data-fx-copied="<?=$this->getTrans('copied') ?>" title="<?=$this->getTrans('copy') ?>">
                                <i class="fa-regular fa-copy"></i> <span><?=$this->getTrans('copy') ?></span>
                            </button>
                        </strong>
                    </div>
                </div>

                <?php if ($payment->isPaypalCheckoutEnabled()) : ?>
                    <div class="fx-pay__method fx-paypal"
                         data-fx-paypal
                         data-sdk="<?=$this->escape($payment->getPaypalSdkUrl($order->getCurrency())) ?>"
                         data-create="<?=$this->escape($this->getUrl(['action' => 'paypalcreate', 'id' => $order->getId()])) ?>"
                         data-capture="<?=$this->escape($this->getUrl(['action' => 'paypalcapture', 'id' => $order->getId()])) ?>"
                         data-token="<?=$this->generateToken() ?>"
                         data-error="<?=$this->escape($this->getTrans('paypalError')) ?>">
                        <h3>
                            <i class="fa-brands fa-paypal"></i> <?=$this->getTrans('paymentPaypalCheckout') ?>
                            <?php if ($payment->isPaypalSandbox()) : ?>
                                <span class="fx-badge fx-badge--soft"><?=$this->getTrans('paypalSandboxBadge') ?></span>
                            <?php endif; ?>
                        </h3>
                        <p class="fx-muted"><?=$this->getTrans('paypalCheckoutHint') ?></p>
                        <button type="button" class="btn fx-btn fx-btn--primary" data-fx-paypal-start>
                            <i class="fa-brands fa-paypal"></i> <?=$this->getTrans('payWithPaypal', $amount) ?>
                        </button>
                        <p class="fx-paypal__consent"><?=$this->getTrans('paypalConsent') ?></p>
                        <div class="fx-paypal__buttons" hidden></div>
                        <p class="fx-paypal__message" role="alert" hidden></p>
                    </div>
                <?php endif; ?>

                <?php if ($payment->isTransferEnabled()) : ?>
                    <div class="fx-pay__method">
                        <h3><i class="fa-solid fa-building-columns"></i> <?=$this->getTrans('paymentTransfer') ?></h3>
                        <p class="fx-pay__bank"><?=nl2br($this->escape($payment->getBankDetails())) ?></p>
                        <p class="fx-muted mb-0"><?=$this->getTrans('transferReferenceHint', $this->escape($order->getReferenceCode())) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($payment->isPaypalMeEnabled()) : ?>
                    <div class="fx-pay__method">
                        <h3><i class="fa-brands fa-paypal"></i> <?=$this->getTrans('paymentPaypalMe') ?></h3>
                        <p class="fx-muted"><?=$this->getTrans('paypalReferenceHint', $this->escape($order->getReferenceCode())) ?></p>
                        <a class="btn fx-btn fx-btn--primary" href="<?=$this->escape($payment->getPaypalMeUrl($order->getAmount(), $order->getCurrency())) ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands fa-paypal"></i> <?=$this->getTrans('payWithPaypal', $amount) ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!$payment->isAvailable()) : ?>
                    <p class="alert alert-warning mb-0"><?=$this->getTrans('noPaymentMethods') ?></p>
                <?php endif; ?>
            </section>
        </div>
        <div class="fx-split__side">
            <aside class="fx-card fx-card--padded">
                <h2 class="fx-section-title"><?=$this->getTrans('whatHappensNext') ?></h2>
                <ol class="fx-next-steps">
                    <li><?=$this->getTrans('nextStepPay') ?></li>
                    <li><?=$this->getTrans('nextStepConfirm') ?></li>
                    <li><?=$this->getTrans('nextStepTrain') ?></li>
                </ol>
                <?php if ($payment->getInfo() !== '') : ?>
                    <p class="fx-note"><?=nl2br($this->escape($payment->getInfo())) ?></p>
                <?php endif; ?>
                <form method="POST" action="<?=$this->getUrl(['action' => 'cancel', 'id' => $order->getId()]) ?>" class="mt-3">
                    <?=$this->getTokenField() ?>
                    <button type="submit" class="btn btn-link fx-undo" data-fx-confirm="<?=$this->getTrans('cancelOrderConfirm') ?>">
                        <?=$this->getTrans('cancelOrder') ?>
                    </button>
                </form>
            </aside>
        </div>
    </div>
<?php elseif ($order->isPaid()) : ?>
    <section class="fx-card fx-card--padded fx-session-done">
        <i class="fa-solid fa-circle-check"></i>
        <div>
            <strong><?=$this->getTrans('orderPaidTitle') ?></strong>
            <?php if ($order->getPaidAt()) : ?>
                <span class="fx-muted"><?=$this->getTrans('paidOn', (new \Ilch\Date($order->getPaidAt()))->format('d.m.Y', true)) ?></span>
            <?php endif; ?>
        </div>
    </section>
    <p class="mt-3">
        <a class="btn fx-btn fx-btn--primary" href="<?=$this->getUrl(['controller' => 'programs', 'action' => 'show', 'id' => $order->getProgramId()]) ?>">
            <i class="fa-solid fa-play"></i> <?=$this->getTrans('toProgram') ?>
        </a>
    </p>
<?php else : ?>
    <p class="fx-empty"><?=$this->getTrans($order->getStatus() === Order::STATUS_REFUNDED ? 'orderRefundedText' : 'orderCancelledText') ?></p>
<?php endif; ?>

<p class="mt-4"><a href="<?=$this->getUrl(['action' => 'index']) ?>"><i class="fa-solid fa-arrow-left"></i> <?=$this->getTrans('myOrders') ?></a></p>
