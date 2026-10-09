<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Mappers;

use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;
use Modules\Fitness\Models\Order as OrderModel;
use Modules\Fitness\Models\Program as ProgramModel;
use Modules\Fitness\Service\Access;
use Modules\Fitness\Service\Orders;
use Modules\User\Config\Config as UserConfig;
use Modules\User\Models\Group as GroupModel;
use Modules\User\Models\User as UserModel;
use PHPUnit\Ilch\DatabaseTestCase;

/**
 * Orders of paid programs and the access they give.
 */
class OrderTest extends DatabaseTestCase
{
    protected Order $out;

    private Orders $service;

    private ProgramModel $program;

    public function setUp(): void
    {
        parent::setUp();
        $this->out = new Order();
        $this->service = new Orders();

        foreach ([1 => 'Admin', 5 => 'Anna', 6 => 'Ben'] as $id => $name) {
            $this->db->insert('users')
                ->values(['id' => $id, 'name' => $name, 'password' => '', 'email' => $name . '@example.org', 'date_created' => '2026-10-09 10:00:00', 'confirmed' => 1, 'locale' => ''])
                ->execute();
        }

        $programMapper = new Program();
        $id = $programMapper->save((new ProgramModel())
            ->setTitle('Bezahlprogramm')
            ->setStatus(ProgramModel::STATUS_PUBLISHED)
            ->setAccessType(ProgramModel::ACCESS_PAID)
            ->setPrice('29,9')
            ->setCurrency('EUR'));
        $this->program = $programMapper->getProgramById($id);
    }

    public function testOrderKeepsThePriceAndGetsAReference()
    {
        $order = $this->service->placeOrder($this->program, 5);

        self::assertSame('29.90', $order->getAmount());
        self::assertSame('EUR', $order->getCurrency());
        self::assertSame(OrderModel::STATUS_OPEN, $order->getStatus());
        self::assertSame('Bezahlprogramm', $order->getProgramTitle());
        self::assertSame('Anna', $order->getUserName());
        self::assertMatchesRegularExpression('/^FIT-[A-HJ-NP-Z2-9]{6}$/', $order->getReferenceCode());

        (new Program())->save($this->program->setPrice('49.00'));
        self::assertSame('29.90', $this->out->getOrderById($order->getId())->getAmount(), 'A later price change does not touch the order.');
    }

    public function testOpenOrderIsUsedAgain()
    {
        $first = $this->service->placeOrder($this->program, 5);
        $second = $this->service->placeOrder($this->program, 5);
        $other = $this->service->placeOrder($this->program, 6);

        self::assertSame($first->getId(), $second->getId());
        self::assertNotSame($first->getId(), $other->getId());
        self::assertNotSame($first->getReferenceCode(), $other->getReferenceCode());
        self::assertCount(1, $this->out->getOrdersOfUser(5));
    }

    public function testConfirmingThePaymentGivesAccessOnce()
    {
        $access = new Access();
        $anna = $this->user(5);
        $order = $this->service->placeOrder($this->program, 5);

        self::assertFalse($access->canViewProgramContent($anna, $this->program), 'An open order gives no access.');

        $enrollment = $this->service->confirmPayment($order, 1, 'transfer', 'Eingang 09.10.');

        self::assertNotNull($enrollment);
        self::assertSame(EnrollmentModel::STATUS_ACTIVE, $enrollment->getStatus());
        self::assertSame(EnrollmentModel::SOURCE_ORDER, $enrollment->getSource());
        self::assertSame($order->getId(), $enrollment->getOrderId());
        self::assertTrue($access->canViewProgramContent($anna, $this->program));

        $stored = $this->out->getOrderById($order->getId());
        self::assertSame(OrderModel::STATUS_PAID, $stored->getStatus());
        self::assertSame('transfer', $stored->getPaymentMethod());
        self::assertSame(1, $stored->getConfirmedBy());
        self::assertSame('Eingang 09.10.', $stored->getNote());
        self::assertNotNull($stored->getPaidAt());

        self::assertNull($this->service->confirmPayment($stored, 1, 'transfer'), 'A paid order can not be confirmed again.');
        self::assertFalse($access->canViewProgramContent($this->user(6), $this->program), 'The payment only opens the program for the buyer.');
    }

    public function testOnlyOpenOrdersCanBeCancelledButLatePaymentsCanBeConfirmed()
    {
        $order = $this->service->placeOrder($this->program, 5);

        self::assertTrue($this->service->cancel($order));
        self::assertFalse($this->service->cancel($order));
        self::assertSame(OrderModel::STATUS_CANCELLED, $this->out->getOrderById($order->getId())->getStatus());
        self::assertNull($this->out->getOpenOrder($this->program->getId(), 5));

        self::assertNotNull($this->service->confirmPayment($order, 1, 'paypalme'), 'Money that arrives after cancelling can still be confirmed.');
    }

    public function testRefundEndsTheParticipationOfThisOrder()
    {
        $order = $this->service->placeOrder($this->program, 5);
        $this->service->confirmPayment($order, 1, 'transfer');

        self::assertTrue($this->service->refund($order, 'Zurücküberwiesen'));
        self::assertFalse($this->service->refund($order), 'A refunded order can not be refunded again.');

        self::assertSame(OrderModel::STATUS_REFUNDED, $this->out->getOrderById($order->getId())->getStatus());
        self::assertSame(EnrollmentModel::STATUS_REVOKED, (new Enrollment())->getEnrollment($this->program->getId(), 5)->getStatus());
        self::assertFalse((new Access())->canViewProgramContent($this->user(5), $this->program));
    }

    public function testNewPaymentReactivatesAnEndedParticipation()
    {
        $first = $this->service->placeOrder($this->program, 5);
        $this->service->confirmPayment($first, 1, 'transfer');
        $this->service->refund($first);

        $second = $this->service->placeOrder($this->program, 5);
        $enrollment = $this->service->confirmPayment($second, 1, 'transfer');

        $stored = (new Enrollment())->getEnrollment($this->program->getId(), 5);
        self::assertSame($enrollment->getId(), $stored->getId(), 'The participation and its progress stay the same.');
        self::assertSame(EnrollmentModel::STATUS_ACTIVE, $stored->getStatus());
        self::assertSame($second->getId(), $stored->getOrderId());
    }

    public function testOrderOfDeletedUserCanNotBeConfirmed()
    {
        $order = $this->service->placeOrder($this->program, 6);
        $this->db->delete('users')->where(['id' => 6])->execute();

        $order = $this->out->getOrderById($order->getId());
        self::assertNull($order->getUserId());
        self::assertNull($this->service->confirmPayment($order, 1, 'transfer'));
    }

    public function testCountsPerStatus()
    {
        $this->service->placeOrder($this->program, 5);
        $this->service->cancel($this->service->placeOrder($this->program, 6));

        self::assertSame([0 => 1, 1 => 0, 2 => 1, 3 => 0], $this->out->getCountsPerStatus());
    }

    public function testOnlyPublishedVisiblePaidProgramsCanBeBought()
    {
        $access = new Access();
        $anna = $this->user(5);

        self::assertTrue($access->canBuy($anna, $this->program, [2]));
        self::assertFalse($access->canBuy(null, $this->program, [3]));
        self::assertFalse($access->canBuy($anna, (clone $this->program)->setStatus(ProgramModel::STATUS_DRAFT), [2]));
        self::assertFalse($access->canBuy($anna, (clone $this->program)->setAccessType(ProgramModel::ACCESS_FREE), [2]));
        self::assertFalse($access->canBuy($anna, (clone $this->program)->setReadAccessAll(false)->setGroupIds([4]), [2]));
        self::assertFalse($access->canJoinForFree($anna, $this->program, [2]), 'A paid program can not be joined for free.');
    }

    private function user(int $id): UserModel
    {
        $user = new UserModel();
        $user->setId($id);
        $user->addGroup((new GroupModel())->setId(2));

        return $user;
    }

    /**
     * Returns database schema sql statements to initialize database
     *
     * @return string
     */
    protected static function getSchemaSQLQueries(): string
    {
        $config = new ModuleConfig();
        $userConfig = new UserConfig();
        $adminConfig = new AdminConfig();

        return $adminConfig->getInstallSql() . $userConfig->getInstallSql() . $config->getInstallSql();
    }
}
