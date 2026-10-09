<?php

/** @var \Ilch\View $this */

use Modules\Fitness\Models\Order;

/** @var Order[] $orders */
$orders = $this->get('orders');
/** @var array<int, int> $counts */
$counts = $this->get('counts');
/** @var int|null $status */
$status = $this->get('status');
/** @var \Ilch\Pagination $pagination */
$pagination = $this->get('pagination');

$badgeClasses = [
    Order::STATUS_OPEN => 'bg-warning text-dark',
    Order::STATUS_PAID => 'bg-success',
    Order::STATUS_CANCELLED => 'bg-secondary',
    Order::STATUS_REFUNDED => 'bg-dark',
];
$filterUrl = array_merge(['action' => 'index'], $status !== null ? ['status' => Order::STATUS_NAMES[$status]] : []);
?>
<h1><?=$this->getTrans('menuOrders') ?></h1>
<p><?=$this->getTrans('ordersIntro') ?></p>

<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link<?=$status === null ? ' active' : '' ?>" href="<?=$this->getUrl(['action' => 'index']) ?>"><?=$this->getTrans('allStates') ?> <span class="badge bg-light text-dark"><?=array_sum($counts) ?></span></a>
    </li>
    <?php foreach (Order::STATUSES as $statusValue => $statusKey) : ?>
        <li class="nav-item">
            <a class="nav-link<?=$status === $statusValue ? ' active' : '' ?>" href="<?=$this->getUrl(['action' => 'index', 'status' => Order::STATUS_NAMES[$statusValue]]) ?>"><?=$this->getTrans($statusKey) ?> <span class="badge bg-light text-dark"><?=$counts[$statusValue] ?? 0 ?></span></a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($orders) : ?>
    <?=$pagination->getHtml($this, $filterUrl) ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle">
            <thead>
                <tr>
                    <th><?=$this->getTrans('orderReference') ?></th>
                    <th><?=$this->getTrans('orderDate') ?></th>
                    <th><?=$this->getTrans('participant') ?></th>
                    <th><?=$this->getTrans('program') ?></th>
                    <th class="text-end"><?=$this->getTrans('amount') ?></th>
                    <th><?=$this->getTrans('status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : ?>
                    <tr>
                        <td><a href="<?=$this->getUrl(['action' => 'show', 'id' => $order->getId()]) ?>"><code><?=$this->escape($order->getReferenceCode()) ?></code></a></td>
                        <td><?=(new \Ilch\Date($order->getCreatedAt()))->format('d.m.Y H:i', true) ?></td>
                        <td><?=$order->getUserId() !== null ? $this->escape($order->getUserName()) : '<span class="text-muted">' . $this->getTrans('deletedUser') . '</span>' ?></td>
                        <td><?=$this->escape($order->getProgramTitle()) ?></td>
                        <td class="text-end"><?=$this->getFormattedCurrency((float)$order->getAmount(), $order->getCurrency()) ?></td>
                        <td><span class="badge <?=$badgeClasses[$order->getStatus()] ?>"><?=$this->getTrans($order->getStatusKey()) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?=$pagination->getHtml($this, $filterUrl) ?>
<?php else : ?>
    <p><?=$this->getTrans('noOrders') ?></p>
<?php endif; ?>
