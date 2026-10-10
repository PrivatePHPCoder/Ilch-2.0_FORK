<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Controllers;

use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Order as OrderMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Models\Order as OrderModel;
use Modules\Fitness\Models\PaymentOptions;
use Modules\Fitness\Service\OrderMails;
use Modules\Fitness\Service\Orders as OrdersService;
use Modules\Fitness\Service\PayPal;
use Modules\Fitness\Service\PayPalCheckout;
use Modules\Fitness\Service\PayPalException;

/**
 * Orders of paid programs: placing an order, the payment page and the list of own orders.
 * Only for logged-in users, and everyone only sees their own orders.
 */
class Orders extends Base
{
    public function init()
    {
        parent::init();
        $this->requireLogin();
    }

    public function indexAction()
    {
        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'))
            ->add($this->getTranslator()->trans('myOrders'));
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('myOrders'), ['action' => 'index']);

        $this->getView()->set('orders', (new OrderMapper())->getOrdersOfUser($this->getUser()->getId()));
    }

    /**
     * The payment page of an order.
     */
    public function showAction()
    {
        $order = $this->loadOwnOrder((int)$this->getRequest()->getParam('id'));

        // Paid with PayPal, but the answer got lost? Ask PayPal before showing the payment page again.
        if ($order->isOpen() && $order->getProviderOrderId() !== null) {
            $paypal = PayPal::fromConfig($this->getConfig());
            try {
                if ($paypal && (new PayPalCheckout($paypal))->sync($order) === PayPal::STATE_PAID) {
                    $this->tellBuyerAboutPayment($order);
                }
            } catch (PayPalException $exception) {
                // PayPal can't be reached right now; the page shows the order as it is stored.
            }
        }

        $this->getLayout()->getTitle()
            ->add($this->getTranslator()->trans('menuFitness'))
            ->add($this->getTranslator()->trans('orderNumber', $order->getReferenceCode()));
        $this->getLayout()->getHmenu()
            ->add($this->getTranslator()->trans('menuFitness'), ['controller' => 'index', 'action' => 'index'])
            ->add($this->getTranslator()->trans('myOrders'), ['action' => 'index'])
            ->add($order->getReferenceCode(), ['action' => 'show', 'id' => $order->getId()]);

        $this->getView()->set('order', $order)
            ->set('payment', PaymentOptions::fromConfig($this->getConfig()));
    }

    /**
     * Places an order for a paid program. Only reachable by POST, which Ilch protects with a token.
     * An open order of the same program is used again instead of placing a second one.
     */
    public function createAction()
    {
        $programId = (int)$this->getRequest()->getParam('program');
        $backToProgram = ['controller' => 'programs', 'action' => 'show', 'id' => $programId];

        if (!$this->getRequest()->isPost()) {
            $this->redirect($backToProgram);
        }

        $program = (new ProgramMapper())->getProgramById($programId);
        if (!$program || !$this->getAccess()->canBuy($this->getUser(), $program, $this->getVisitorGroupIds())) {
            $this->redirect()
                ->withMessage('programNotFound', 'warning')
                ->to(['controller' => 'programs', 'action' => 'index']);
        }

        $userId = $this->getUser()->getId();
        if ((new EnrollmentMapper())->getEnrollment($program->getId(), $userId)) {
            $this->redirect()
                ->withMessage('alreadyJoined', 'info')
                ->to($backToProgram);
        }

        if (!PaymentOptions::fromConfig($this->getConfig())->isAvailable()) {
            $this->redirect()
                ->withMessage('buyNotAvailable', 'warning')
                ->to($backToProgram);
        }

        $orderMapper = new OrderMapper();
        $openOrder = $orderMapper->getOpenOrder($program->getId(), $userId);
        if ($openOrder) {
            $this->redirect()
                ->withMessage('orderStillOpen', 'info')
                ->to(['action' => 'show', 'id' => $openOrder->getId()]);
        }

        $order = (new OrdersService())->placeOrder($program, $userId);
        $this->sendOrderMails($order);

        $this->redirect()
            ->withMessage('orderPlaced')
            ->to(['action' => 'show', 'id' => $order->getId()]);
    }

    /**
     * Creates the PayPal order when the buyer clicks the PayPal button. Called by the page's
     * JavaScript with POST, answers with JSON. The amount comes from the stored order.
     */
    public function paypalcreateAction()
    {
        $order = $this->loadOrderForPaypal();
        if ($order === null) {
            return;
        }

        try {
            $paypalOrderId = (new PayPalCheckout(PayPal::fromConfig($this->getConfig())))->start(
                $order,
                $order->getProgramTitle(),
                (string)$this->getConfig()->get('page_title')
            );
        } catch (PayPalException $exception) {
            $key = [
                'ORDER_ALREADY_PAID' => 'paypalAlreadyPaid',
                'PAYMENT_PENDING' => 'paypalPending',
                'ORDER_NOT_OPEN' => 'orderNotOpen',
            ][$exception->getIssue()] ?? 'paypalError';
            $this->sendJson(['message' => $this->getTranslator()->trans($key)]);
            return;
        }

        $this->sendJson(['id' => $paypalOrderId]);
    }

    /**
     * Books the payment after the buyer approved it at PayPal. Called by the page's JavaScript with
     * POST, answers with JSON. Access is only given if PayPal confirms the booked amount.
     */
    public function paypalcaptureAction()
    {
        $order = $this->loadOrderForPaypal();
        if ($order === null) {
            return;
        }

        $wasOpen = $order->isOpen();
        try {
            $state = (new PayPalCheckout(PayPal::fromConfig($this->getConfig())))
                ->finish($order, (string)$this->getRequest()->getPost('paypalOrderId'));
        } catch (PayPalException $exception) {
            // Declined card or similar: the PayPal window lets the buyer choose another way.
            $this->sendJson($exception->getIssue() === 'INSTRUMENT_DECLINED'
                ? ['state' => 'declined']
                : ['state' => 'error', 'message' => $this->getTranslator()->trans('paypalError')]);
            return;
        }

        if ($state === PayPal::STATE_PAID) {
            // A second request for the same payment must not send the e-mail again.
            if ($wasOpen) {
                $this->tellBuyerAboutPayment($order);
            }
            $this->addMessage('paypalPaid');
            $this->sendJson([
                'state' => 'paid',
                'redirect' => $this->getLayout()->getUrl(['controller' => 'programs', 'action' => 'show', 'id' => $order->getProgramId()]),
            ]);
            return;
        }

        $this->sendJson([
            'state' => $state,
            'message' => $this->getTranslator()->trans($state === PayPal::STATE_PENDING ? 'paypalPending' : 'paypalFailed'),
        ]);
    }

    /**
     * Loads the order of the current user for the PayPal actions. Answers with a JSON error and
     * returns null if it is not a POST request, PayPal Checkout is not set up or the order is not
     * the user's open order.
     *
     * @return OrderModel|null
     */
    private function loadOrderForPaypal(): ?OrderModel
    {
        $order = (new OrderMapper())->getOrderById((int)$this->getRequest()->getParam('id'));

        if (!$this->getRequest()->isPost() || !PayPal::fromConfig($this->getConfig())) {
            $this->sendJson(['state' => 'error', 'message' => $this->getTranslator()->trans('paypalError')]);
            return null;
        }

        if (!$order || $order->getUserId() !== $this->getUser()->getId()) {
            $this->sendJson(['state' => 'error', 'message' => $this->getTranslator()->trans('orderNotFound')]);
            return null;
        }

        return $order;
    }

    /**
     * Answers with JSON instead of a page. The view of the action prints the data.
     *
     * @param array $data
     */
    private function sendJson(array $data): void
    {
        $this->getLayout()->setDisabled(true);
        header('Content-Type: application/json; charset=utf-8');
        $this->getView()->set('json', $data);
    }

    /**
     * Tells the buyer about the confirmed payment.
     *
     * @param OrderModel $order
     */
    private function tellBuyerAboutPayment(OrderModel $order): void
    {
        (new OrderMails($this->getLayout()))->sendPaymentConfirmation(
            $order,
            $this->getLayout()->getUrl(['module' => 'fitness', 'controller' => 'programs', 'action' => 'show', 'id' => $order->getProgramId()])
        );
    }

    /**
     * Cancels an open order of the user. Only reachable by POST.
     */
    public function cancelAction()
    {
        $id = (int)$this->getRequest()->getParam('id');

        if (!$this->getRequest()->isPost()) {
            $this->redirect(['action' => 'show', 'id' => $id]);
        }

        $order = $this->loadOwnOrder($id);
        $cancelled = (new OrdersService())->cancel($order);

        $this->redirect()
            ->withMessage($cancelled ? 'orderCancelledByUser' : 'orderNotOpen', $cancelled ? 'info' : 'warning')
            ->to(['action' => 'show', 'id' => $order->getId()]);
    }

    /**
     * Loads an order of the current user. Orders of other users are treated as not existing.
     *
     * @param int $id
     * @return OrderModel
     */
    private function loadOwnOrder(int $id): OrderModel
    {
        $order = (new OrderMapper())->getOrderById($id);

        if (!$order || $order->getUserId() !== $this->getUser()->getId()) {
            $this->redirect()
                ->withMessage('orderNotFound', 'warning')
                ->to(['action' => 'index']);
        }

        return $order;
    }

    /**
     * Sends the confirmation to the buyer and, if set up, a notice to the admins.
     *
     * @param OrderModel $order
     */
    private function sendOrderMails(OrderModel $order): void
    {
        $mails = new OrderMails($this->getLayout());
        $user = $this->getUser();

        $mails->sendForOrder(
            OrderMails::TYPE_CREATED,
            $order,
            $user->getEmail(),
            $user->getName(),
            $this->getTranslator()->getLocale(),
            ['{orderLink}' => $this->getLayout()->getUrl(['module' => 'fitness', 'controller' => 'orders', 'action' => 'show', 'id' => $order->getId()])]
        );

        $notifyEmail = (string)$this->getConfig()->get('fitness_orderNotifyEmail');
        if ($notifyEmail !== '') {
            $mails->sendForOrder(
                OrderMails::TYPE_ADMIN,
                $order,
                $notifyEmail,
                '',
                (string)$this->getConfig()->get('locale'),
                ['{orderLink}' => $this->getLayout()->getUrl(['module' => 'fitness', 'controller' => 'orders', 'action' => 'show', 'id' => $order->getId()], 'admin')]
            );
        }
    }
}
