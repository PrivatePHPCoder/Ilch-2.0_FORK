<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Mappers\Order as OrderMapper;
use Modules\Fitness\Models\Order as OrderModel;

/**
 * Payment of an order with PayPal Checkout. The browser only starts the payment; whether it was
 * successful is always asked from PayPal by the server.
 */
class PayPalCheckout
{
    /**
     * @var PayPal
     */
    private PayPal $paypal;

    /**
     * @var OrderMapper
     */
    private OrderMapper $orderMapper;

    /**
     * @var Orders
     */
    private Orders $orders;

    public function __construct(PayPal $paypal, ?OrderMapper $orderMapper = null, ?Orders $orders = null)
    {
        $this->paypal = $paypal;
        $this->orderMapper = $orderMapper ?? new OrderMapper();
        $this->orders = $orders ?? new Orders();
    }

    /**
     * Creates the PayPal order for an open order and remembers its id.
     *
     * If an earlier PayPal payment of this order exists, PayPal is asked about it first. A payment
     * that was booked but not noticed (for example after a lost connection) must not lead to a
     * second payment.
     *
     * @param OrderModel $order
     * @param string $description shown to the buyer at PayPal
     * @param string $brandName name of the website at PayPal
     * @return string id of the PayPal order
     */
    public function start(OrderModel $order, string $description, string $brandName): string
    {
        if (!$order->isOpen()) {
            throw new PayPalException('The order is not open.', 'ORDER_NOT_OPEN');
        }

        if ($order->getProviderOrderId() !== null) {
            $state = $this->sync($order);
            if ($state === PayPal::STATE_PAID) {
                throw new PayPalException('The order is paid already.', 'ORDER_ALREADY_PAID');
            }
            if ($state === PayPal::STATE_PENDING) {
                throw new PayPalException('An earlier payment is still pending.', 'PAYMENT_PENDING');
            }
        }

        $paypalOrderId = $this->paypal->createOrder($order, $description, $brandName);
        $this->orderMapper->updateProviderIds($order->setProviderOrderId($paypalOrderId));

        return $paypalOrderId;
    }

    /**
     * Captures the payment the buyer approved at PayPal and confirms the order if PayPal booked the
     * money. Calling it again for a paid order changes nothing.
     *
     * @param OrderModel $order
     * @param string $paypalOrderId id the browser reports, must be the one stored by start()
     * @return string one of PayPal::STATE_*
     */
    public function finish(OrderModel $order, string $paypalOrderId): string
    {
        if ($order->isPaid()) {
            return PayPal::STATE_PAID;
        }

        if (!$order->isOpen() || $paypalOrderId === '' || $paypalOrderId !== $order->getProviderOrderId()) {
            return PayPal::STATE_INVALID;
        }

        try {
            $paypalOrder = $this->paypal->captureOrder($paypalOrderId);
        } catch (PayPalException $exception) {
            if ($exception->getIssue() !== 'ORDER_ALREADY_CAPTURED') {
                throw $exception;
            }
            // A second request was faster, or the answer got lost: ask for the current state.
            $paypalOrder = $this->paypal->getOrder($paypalOrderId);
        }

        return $this->apply($order, $paypalOrder);
    }

    /**
     * Asks PayPal about an open order that already has a PayPal order, for example when the
     * connection broke after the buyer paid.
     *
     * @param OrderModel $order
     * @return string one of PayPal::STATE_*
     */
    public function sync(OrderModel $order): string
    {
        if ($order->isPaid()) {
            return PayPal::STATE_PAID;
        }

        if (!$order->isOpen() || $order->getProviderOrderId() === null) {
            return PayPal::STATE_OPEN;
        }

        try {
            $paypalOrder = $this->paypal->getOrder($order->getProviderOrderId());
        } catch (PayPalException $exception) {
            // PayPal removes orders that were never paid after a while.
            if ($exception->getCode() === 404) {
                return PayPal::STATE_OPEN;
            }
            throw $exception;
        }

        return $this->apply($order, $paypalOrder);
    }

    /**
     * Checks the PayPal order against the own order, stores the capture id and confirms the payment
     * if the money is booked.
     *
     * @param OrderModel $order
     * @param array $paypalOrder
     * @return string
     */
    private function apply(OrderModel $order, array $paypalOrder): string
    {
        if (($paypalOrder['id'] ?? null) !== $order->getProviderOrderId()) {
            return PayPal::STATE_INVALID;
        }

        $result = PayPal::evaluate($paypalOrder, $order);

        if ($result['captureId'] !== null && $result['captureId'] !== $order->getProviderCaptureId()) {
            $this->orderMapper->updateProviderIds($order->setProviderCaptureId($result['captureId']));
        }

        if ($result['state'] === PayPal::STATE_PAID) {
            $note = trim($order->getNote() . "\nPayPal " . $result['captureId']);
            $this->orders->confirmPayment($order, null, 'paypal', $note);
        }

        return $result['state'];
    }
}
