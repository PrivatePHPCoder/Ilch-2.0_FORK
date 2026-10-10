<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Mappers;

use Ilch\Date;
use Ilch\Pagination;
use Modules\Fitness\Models\Order as OrderModel;

class Order extends Base
{
    /**
     * @var string
     */
    public string $tablename = 'fitness_orders';

    /**
     * Letters and digits of the reference code. Without 0, O, 1 and I, which are easy to mix up.
     */
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Number of random characters after the prefix.
     */
    private const CODE_LENGTH = 6;

    /**
     * How often a new reference code is tried when the code is already taken.
     */
    private const CODE_ATTEMPTS = 5;

    /**
     * Returns orders with program title, user name and e-mail address.
     *
     * @param array $where
     * @param array $orderBy
     * @param Pagination|null $pagination
     * @return OrderModel[]
     */
    public function getEntriesBy(array $where = [], array $orderBy = ['o.created_at' => 'DESC', 'o.id' => 'DESC'], ?Pagination $pagination = null): array
    {
        $select = $this->db()->select(['o.id', 'o.user_id', 'o.program_id', 'o.amount', 'o.currency', 'o.status', 'o.payment_method', 'o.reference_code', 'o.provider_order_id', 'o.provider_capture_id', 'o.created_at', 'o.paid_at', 'o.confirmed_by', 'o.note'])
            ->from(['o' => $this->tablename])
            ->join(['p' => 'fitness_programs'], 'p.id = o.program_id', 'INNER', ['program_title' => 'p.title'])
            ->join(['u' => 'users'], 'u.id = o.user_id', 'LEFT', ['user_name' => 'u.name', 'user_email' => 'u.email'])
            ->where($where)
            ->order($orderBy);

        if ($pagination !== null) {
            $select->limit($pagination->getLimit())
                ->useFoundRows();
            $result = $select->execute();
            $pagination->setRows($result->getFoundRows());
        } else {
            $result = $select->execute();
        }

        $orders = [];
        foreach ($result->fetchRows() as $row) {
            $orders[] = (new OrderModel())->setByArray($row);
        }

        return $orders;
    }

    public function getOrderById(int $id): ?OrderModel
    {
        $orders = $this->getEntriesBy(['o.id' => $id]);

        return reset($orders) ?: null;
    }

    /**
     * @param int $userId
     * @return OrderModel[]
     */
    public function getOrdersOfUser(int $userId): array
    {
        return $this->getEntriesBy(['o.user_id' => $userId]);
    }

    /**
     * Returns the open order of a user for a program, if there is one.
     *
     * @param int $programId
     * @param int $userId
     * @return OrderModel|null
     */
    public function getOpenOrder(int $programId, int $userId): ?OrderModel
    {
        $orders = $this->getEntriesBy(['o.program_id' => $programId, 'o.user_id' => $userId, 'o.status' => OrderModel::STATUS_OPEN]);

        return reset($orders) ?: null;
    }

    /**
     * Stores a new order with the creation date and a new reference code.
     *
     * @param OrderModel $order
     * @return int id of the order
     */
    public function create(OrderModel $order): int
    {
        $order->setCreatedAt((new Date())->toDb());

        // The reference code is unique. In the unlikely case that a code is already taken, try another one.
        $lastException = null;
        for ($attempt = 0; $attempt < self::CODE_ATTEMPTS; $attempt++) {
            $order->setReferenceCode(self::generateReferenceCode());

            try {
                $id = (int)$this->db()->insert($this->tablename)
                    ->values($order->getArray(false))
                    ->execute();
                $order->setId($id);

                return $id;
            } catch (\Exception $exception) {
                $lastException = $exception;
            }
        }

        throw $lastException;
    }

    /**
     * Saves status, payment details and note of an order.
     *
     * @param OrderModel $order
     */
    public function update(OrderModel $order): void
    {
        $this->updateRow($this->tablename, $order->getId(), [
            'status' => $order->getStatus(),
            'payment_method' => $order->getPaymentMethod(),
            'paid_at' => $order->getPaidAt(),
            'confirmed_by' => $order->getConfirmedBy(),
            'note' => $order->getNote(),
        ]);
    }

    /**
     * Stores the ids PayPal gave the order and its capture.
     *
     * @param OrderModel $order
     */
    public function updateProviderIds(OrderModel $order): void
    {
        $this->updateRow($this->tablename, $order->getId(), [
            'provider_order_id' => $order->getProviderOrderId(),
            'provider_capture_id' => $order->getProviderCaptureId(),
        ]);
    }

    /**
     * Returns the number of orders per status.
     *
     * @return array<int, int> status => number of orders
     */
    public function getCountsPerStatus(): array
    {
        $rows = $this->db()->select(['status', 'order_count' => 'COUNT(*)'])
            ->from($this->tablename)
            ->group(['status'])
            ->execute()
            ->fetchRows();

        $counts = array_fill_keys(array_keys(OrderModel::STATUSES), 0);
        foreach ($rows as $row) {
            $counts[(int)$row['status']] = (int)$row['order_count'];
        }

        return $counts;
    }

    /**
     * Returns a new reference code like "FIT-7K3M9Q".
     *
     * @return string
     */
    public static function generateReferenceCode(): string
    {
        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
        }

        return 'FIT-' . $code;
    }
}
