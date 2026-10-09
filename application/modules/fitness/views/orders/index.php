<?php

/** @var \Ilch\View $this */

/** @var \Modules\Fitness\Models\Order[] $orders */
$orders = $this->get('orders');
?>
<header class="fx-page-head">
    <h1 class="fx-page-title"><?=$this->getTrans('myOrders') ?></h1>
    <p class="fx-page-lead"><?=$this->getTrans('myOrdersLead') ?></p>
</header>

<?php if ($orders) : ?>
    <div class="fx-card">
        <div class="table-responsive">
            <table class="table fx-table mb-0">
                <thead>
                    <tr>
                        <th><?=$this->getTrans('orderReference') ?></th>
                        <th><?=$this->getTrans('program') ?></th>
                        <th><?=$this->getTrans('orderDate') ?></th>
                        <th class="text-end"><?=$this->getTrans('amount') ?></th>
                        <th><?=$this->getTrans('status') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order) : ?>
                        <tr>
                            <td><a href="<?=$this->getUrl(['action' => 'show', 'id' => $order->getId()]) ?>"><code><?=$this->escape($order->getReferenceCode()) ?></code></a></td>
                            <td><?=$this->escape($order->getProgramTitle()) ?></td>
                            <td><?=(new \Ilch\Date($order->getCreatedAt()))->format('d.m.Y', true) ?></td>
                            <td class="text-end"><?=$this->getFormattedCurrency((float)$order->getAmount(), $order->getCurrency()) ?></td>
                            <td><span class="fx-order-status fx-order-status--<?=$order->getStatus() ?>"><?=$this->getTrans($order->getStatusKey()) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else : ?>
    <div class="fx-empty">
        <p><?=$this->getTrans('noOrdersYet') ?></p>
        <a class="btn fx-btn fx-btn--primary" href="<?=$this->getUrl(['controller' => 'programs', 'action' => 'index']) ?>"><?=$this->getTrans('showPrograms') ?></a>
    </div>
<?php endif; ?>
