<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers\Admin;

use Ilch\Pagination;
use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Order as OrderMapper;
use Modules\Fitness\Models\Order as OrderModel;
use Modules\Fitness\Service\OrderMails;
use Modules\Fitness\Service\Orders as OrdersService;
use Modules\Fitness\Service\PayPal;
use Modules\Fitness\Service\PayPalCheckout;
use Modules\Fitness\Service\PayPalException;
use Modules\User\Mappers\User as UserMapper;

/**
 * Orders of paid programs. Confirming the payment gives the buyer access.
 */
class Orders extends Base
{
    /**
     * Maximum length of the internal note.
     */
    private const MAX_NOTE_LENGTH = 2000;

    public function indexAction()
    {
        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuOrders'), ['action' => 'index']);

        $status = array_search((string)$this->getRequest()->getParam('status'), OrderModel::STATUS_NAMES, true);
        $status = $status === false ? null : $status;

        $pagination = new Pagination();
        $pagination->setRowsPerPage($this->getConfig()->get('defaultPaginationObjects'));
        $pagination->setPage($this->getRequest()->getParam('page', 1));

        $orderMapper = new OrderMapper();
        $this->getView()->set('orders', $orderMapper->getEntriesBy($status !== null ? ['o.status' => $status] : [], ['o.created_at' => 'DESC', 'o.id' => 'DESC'], $pagination))
            ->set('counts', $orderMapper->getCountsPerStatus())
            ->set('status', $status)
            ->set('pagination', $pagination);
    }

    public function showAction()
    {
        $order = $this->loadOrder((int)$this->getRequest()->getParam('id'));

        $this->getLayout()->getAdminHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('menuOrders'), ['action' => 'index'])
            ->add($order->getReferenceCode(), ['action' => 'show', 'id' => $order->getId()]);

        $enrollment = $order->getUserId() !== null ? (new EnrollmentMapper())->getEnrollment($order->getProgramId(), $order->getUserId()) : null;
        $confirmedBy = $order->getConfirmedBy() !== null ? (new UserMapper())->getUserById($order->getConfirmedBy()) : null;

        $this->getView()->set('order', $order)
            ->set('enrollment', $enrollment)
            ->set('confirmedByName', $confirmedBy ? $confirmedBy->getName() : '');
    }

    /**
     * Confirms the payment, cancels or refunds an order, or only saves the note. Only reachable by POST.
     */
    public function statusAction()
    {
        $id = (int)$this->getRequest()->getPost('id');
        $back = ['action' => 'show', 'id' => $id];

        if (!$this->getRequest()->isPost()) {
            $this->redirect(['action' => 'index']);
        }

        $order = $this->loadOrder($id);
        $note = mb_substr(trim((string)$this->getRequest()->getPost('note')), 0, self::MAX_NOTE_LENGTH);
        $service = new OrdersService();

        switch ((string)$this->getRequest()->getPost('change')) {
            case 'confirm':
                $method = (string)$this->getRequest()->getPost('method');
                if (!isset(OrderModel::METHODS[$method])) {
                    $this->redirect()
                        ->withMessage('paymentMethodMissing', 'danger')
                        ->to($back);
                }

                if (!$service->confirmPayment($order, $this->getUser()->getId(), $method, $note)) {
                    $this->redirect()
                        ->withMessage('orderChangeInvalid', 'warning')
                        ->to($back);
                }

                $mailSent = $this->tellBuyerAboutPayment($order);
                $this->redirect()
                    ->withMessage('orderConfirmed')
                    ->withMessage($mailSent ? 'buyerMailSent' : 'buyerMailFailed', $mailSent ? 'info' : 'warning')
                    ->to($back);
                break;
            case 'paypalsync':
                $result = $this->syncWithPaypal($order);
                $this->redirect()
                    ->withMessage($result, $result === 'paypalSyncPaid' ? 'success' : 'info')
                    ->to($back);
                break;
            case 'cancel':
                $done = $service->cancel($order, $note);
                break;
            case 'refund':
                $done = $service->refund($order, $note);
                break;
            case 'note':
                (new OrderMapper())->update($order->setNote($note));
                $done = true;
                break;
            default:
                $done = false;
        }

        $this->redirect()
            ->withMessage($done ? 'saveSuccess' : 'orderChangeInvalid', $done ? 'success' : 'warning')
            ->to($back);
    }

    /**
     * @param int $id
     * @return OrderModel
     */
    private function loadOrder(int $id): OrderModel
    {
        $order = (new OrderMapper())->getOrderById($id);

        if (!$order) {
            $this->redirect()
                ->withMessage('entryNotFound', 'danger')
                ->to(['action' => 'index']);
        }

        return $order;
    }

    /**
     * Sends the buyer a notification and an e-mail that the program is unlocked.
     *
     * @param OrderModel $order
     * @return bool whether the e-mail was sent
     */
    private function tellBuyerAboutPayment(OrderModel $order): bool
    {
        return (new OrderMails($this->getLayout()))->sendPaymentConfirmation(
            $order,
            $this->getLayout()->getUrl(['module' => 'fitness', 'controller' => 'programs', 'action' => 'show', 'id' => $order->getProgramId()], '')
        );
    }

    /**
     * Asks PayPal about an open order paid with PayPal Checkout and confirms it if PayPal booked
     * the money.
     *
     * @param OrderModel $order
     * @return string translation key of the result
     */
    private function syncWithPaypal(OrderModel $order): string
    {
        $paypal = PayPal::fromConfig($this->getConfig());
        if (!$paypal || $order->getProviderOrderId() === null) {
            return 'paypalSyncNotPossible';
        }

        try {
            $state = (new PayPalCheckout($paypal))->sync($order);
        } catch (PayPalException $exception) {
            return 'paypalUnreachable';
        }

        if ($state === PayPal::STATE_PAID && $order->isPaid()) {
            $this->tellBuyerAboutPayment($order);
        }

        return [
            PayPal::STATE_PAID => 'paypalSyncPaid',
            PayPal::STATE_PENDING => 'paypalSyncPending',
            PayPal::STATE_OPEN => 'paypalSyncOpen',
        ][$state] ?? 'paypalSyncInvalid';
    }
}
