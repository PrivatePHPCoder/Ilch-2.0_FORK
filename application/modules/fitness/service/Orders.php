<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Ilch\Date;
use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Order as OrderMapper;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;
use Modules\Fitness\Models\Order as OrderModel;
use Modules\Fitness\Models\Program as ProgramModel;

/**
 * Orders of paid programs. Access is only given when an admin confirms the payment, never
 * because of something the buyer's browser sends.
 */
class Orders
{
    /**
     * @var OrderMapper
     */
    private OrderMapper $orderMapper;

    /**
     * @var EnrollmentMapper
     */
    private EnrollmentMapper $enrollmentMapper;

    /**
     * @var Enrollments
     */
    private Enrollments $enrollments;

    public function __construct(?OrderMapper $orderMapper = null, ?EnrollmentMapper $enrollmentMapper = null, ?Enrollments $enrollments = null)
    {
        $this->orderMapper = $orderMapper ?? new OrderMapper();
        $this->enrollmentMapper = $enrollmentMapper ?? new EnrollmentMapper();
        $this->enrollments = $enrollments ?? new Enrollments();
    }

    /**
     * Returns the open order of the user for the program, or places a new one with the current price.
     *
     * @param ProgramModel $program
     * @param int $userId
     * @return OrderModel
     */
    public function placeOrder(ProgramModel $program, int $userId): OrderModel
    {
        $openOrder = $this->orderMapper->getOpenOrder($program->getId(), $userId);
        if ($openOrder) {
            return $openOrder;
        }

        $id = $this->orderMapper->create((new OrderModel())
            ->setUserId($userId)
            ->setProgramId($program->getId())
            ->setAmount($program->getPrice())
            ->setCurrency($program->getCurrency()));

        return $this->orderMapper->getOrderById($id);
    }

    /**
     * Marks the order as paid and gives the buyer access to the program. A cancelled order can be
     * confirmed as well, for example when the money arrives late.
     *
     * @param OrderModel $order
     * @param int|null $adminId admin who confirms the payment, null if PayPal confirmed it
     * @param string $method one of the keys of OrderModel::METHODS
     * @param string $note internal note
     * @return EnrollmentModel|null the participation, or null if the order can't be confirmed
     */
    public function confirmPayment(OrderModel $order, ?int $adminId, string $method, string $note = ''): ?EnrollmentModel
    {
        if ($order->getUserId() === null || !in_array($order->getStatus(), [OrderModel::STATUS_OPEN, OrderModel::STATUS_CANCELLED], true)) {
            return null;
        }

        $order->setStatus(OrderModel::STATUS_PAID)
            ->setPaidAt((new Date())->toDb())
            ->setConfirmedBy($adminId)
            ->setPaymentMethod($method)
            ->setNote($note);
        $this->orderMapper->update($order);

        $enrollment = $this->enrollmentMapper->getEnrollment($order->getProgramId(), $order->getUserId());
        if (!$enrollment) {
            return $this->enrollmentMapper->enroll($order->getProgramId(), $order->getUserId(), EnrollmentModel::SOURCE_ORDER, $order->getId());
        }

        $this->enrollmentMapper->updateSource($enrollment, EnrollmentModel::SOURCE_ORDER, $order->getId());
        if (!$enrollment->grantsAccess()) {
            $this->enrollmentMapper->updateStatus($enrollment, $this->enrollments->getReactivationStatus($enrollment));
        }

        return $enrollment;
    }

    /**
     * Cancels an open order.
     *
     * @param OrderModel $order
     * @param string|null $note new internal note, null keeps the current one
     * @return bool false if the order is not open
     */
    public function cancel(OrderModel $order, ?string $note = null): bool
    {
        if (!$order->isOpen()) {
            return false;
        }

        $order->setStatus(OrderModel::STATUS_CANCELLED);
        if ($note !== null) {
            $order->setNote($note);
        }
        $this->orderMapper->update($order);

        return true;
    }

    /**
     * Marks a paid order as refunded and ends the participation that came from it.
     *
     * @param OrderModel $order
     * @param string|null $note new internal note, null keeps the current one
     * @return bool false if the order is not paid
     */
    public function refund(OrderModel $order, ?string $note = null): bool
    {
        if (!$order->isPaid()) {
            return false;
        }

        $order->setStatus(OrderModel::STATUS_REFUNDED);
        if ($note !== null) {
            $order->setNote($note);
        }
        $this->orderMapper->update($order);

        $enrollment = $order->getUserId() !== null ? $this->enrollmentMapper->getEnrollment($order->getProgramId(), $order->getUserId()) : null;
        if ($enrollment && $enrollment->getOrderId() === $order->getId() && $enrollment->getStatus() !== EnrollmentModel::STATUS_REVOKED) {
            $this->enrollmentMapper->updateStatus($enrollment, EnrollmentModel::STATUS_REVOKED);
        }

        return true;
    }
}
