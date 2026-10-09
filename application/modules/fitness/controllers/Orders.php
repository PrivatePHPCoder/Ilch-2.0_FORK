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
