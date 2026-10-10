<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Service;

use Modules\Admin\Config\Config as AdminConfig;
use Modules\Fitness\Config\Config as ModuleConfig;
use Modules\Fitness\Mappers\Enrollment as EnrollmentMapper;
use Modules\Fitness\Mappers\Order as OrderMapper;
use Modules\Fitness\Mappers\Program as ProgramMapper;
use Modules\Fitness\Models\Enrollment as EnrollmentModel;
use Modules\Fitness\Models\Order as OrderModel;
use Modules\Fitness\Models\Program as ProgramModel;
use Modules\User\Config\Config as UserConfig;
use PHPUnit\Ilch\DatabaseTestCase;

require_once __DIR__ . '/FakePayPalServer.php';

/**
 * Paying an order with PayPal Checkout. Access is only given for a capture PayPal confirms.
 */
class PayPalCheckoutTest extends DatabaseTestCase
{
    private FakePayPalServer $server;

    private OrderModel $order;

    public function setUp(): void
    {
        parent::setUp();
        $this->server = new FakePayPalServer();

        $this->db->insert('users')
            ->values(['id' => 5, 'name' => 'Anna', 'password' => '', 'email' => 'anna@example.org', 'date_created' => '2026-10-10 10:00:00', 'confirmed' => 1, 'locale' => ''])
            ->execute();
        $programMapper = new ProgramMapper();
        $programId = $programMapper->save((new ProgramModel())
            ->setTitle('Kraft-Aufbau')
            ->setStatus(ProgramModel::STATUS_PUBLISHED)
            ->setAccessType(ProgramModel::ACCESS_PAID)
            ->setPrice('19.90'));
        $this->order = (new Orders())->placeOrder($programMapper->getProgramById($programId), 5);
    }

    private function checkout(): PayPalCheckout
    {
        return new PayPalCheckout($this->server->client());
    }

    private function storedOrder(): OrderModel
    {
        return (new OrderMapper())->getOrderById($this->order->getId());
    }

    private function startPayment(): string
    {
        $this->server->answer('POST', '/v2/checkout/orders', 201, ['id' => 'PAYPAL-1', 'status' => 'CREATED']);

        return $this->checkout()->start($this->order, 'Kraft-Aufbau', 'Studio');
    }

    public function testStartRemembersThePayPalOrder()
    {
        self::assertSame('PAYPAL-1', $this->startPayment());
        self::assertSame('PAYPAL-1', $this->storedOrder()->getProviderOrderId());
    }

    public function testCompletedCaptureConfirmsTheOrderAndGivesAccess()
    {
        $this->startPayment();
        $this->server->answer('POST', '/PAYPAL-1/capture', 201, FakePayPalServer::capturedOrder('PAYPAL-1', 'COMPLETED', '19.90', 'EUR', (string)$this->order->getId()));

        self::assertSame(PayPal::STATE_PAID, $this->checkout()->finish($this->storedOrder(), 'PAYPAL-1'));

        $stored = $this->storedOrder();
        self::assertSame(OrderModel::STATUS_PAID, $stored->getStatus());
        self::assertSame('paypal', $stored->getPaymentMethod());
        self::assertNull($stored->getConfirmedBy(), 'PayPal confirmed it, not an admin.');
        self::assertSame('CAPTURE-PAYPAL-1', $stored->getProviderCaptureId());
        self::assertStringContainsString('CAPTURE-PAYPAL-1', $stored->getNote());
        self::assertSame(EnrollmentModel::STATUS_ACTIVE, (new EnrollmentMapper())->getEnrollment($stored->getProgramId(), 5)->getStatus());

        $requests = count($this->server->requests);
        self::assertSame(PayPal::STATE_PAID, $this->checkout()->finish($stored, 'PAYPAL-1'), 'Finishing again changes nothing.');
        self::assertCount($requests, $this->server->requests, 'No second capture is sent.');
    }

    public function testPayPalOrderIdFromTheBrowserMustMatch()
    {
        $this->startPayment();

        self::assertSame(PayPal::STATE_INVALID, $this->checkout()->finish($this->storedOrder(), 'OTHER-ORDER'));
        self::assertSame([], array_filter($this->server->apiRequests(), static fn ($request) => strpos($request['url'], 'capture') !== false));
        self::assertTrue($this->storedOrder()->isOpen());
    }

    public function testWrongAmountKeepsTheOrderOpen()
    {
        $this->startPayment();
        $this->server->answer('POST', '/PAYPAL-1/capture', 201, FakePayPalServer::capturedOrder('PAYPAL-1', 'COMPLETED', '1.00', 'EUR', (string)$this->order->getId()));

        self::assertSame(PayPal::STATE_INVALID, $this->checkout()->finish($this->storedOrder(), 'PAYPAL-1'));
        self::assertTrue($this->storedOrder()->isOpen());
        self::assertNull((new EnrollmentMapper())->getEnrollment($this->order->getProgramId(), 5));
    }

    public function testPendingPaymentKeepsTheOrderOpen()
    {
        $this->startPayment();
        $this->server->answer('POST', '/PAYPAL-1/capture', 201, FakePayPalServer::capturedOrder('PAYPAL-1', 'PENDING', '19.90', 'EUR', (string)$this->order->getId()));

        self::assertSame(PayPal::STATE_PENDING, $this->checkout()->finish($this->storedOrder(), 'PAYPAL-1'));
        self::assertTrue($this->storedOrder()->isOpen());
        self::assertSame('CAPTURE-PAYPAL-1', $this->storedOrder()->getProviderCaptureId());
    }

    public function testAlreadyCapturedOrderIsReadAgain()
    {
        $this->startPayment();
        $this->server
            ->answer('POST', '/PAYPAL-1/capture', 422, ['name' => 'UNPROCESSABLE_ENTITY', 'details' => [['issue' => 'ORDER_ALREADY_CAPTURED']]])
            ->answer('GET', '/v2/checkout/orders/PAYPAL-1', 200, FakePayPalServer::capturedOrder('PAYPAL-1', 'COMPLETED', '19.90', 'EUR', (string)$this->order->getId()));

        self::assertSame(PayPal::STATE_PAID, $this->checkout()->finish($this->storedOrder(), 'PAYPAL-1'));
        self::assertTrue($this->storedOrder()->isPaid());
    }

    public function testDeclinedPaymentIsReportedToTheCaller()
    {
        $this->startPayment();
        $this->server->answer('POST', '/PAYPAL-1/capture', 422, ['name' => 'UNPROCESSABLE_ENTITY', 'details' => [['issue' => 'INSTRUMENT_DECLINED']]]);

        try {
            $this->checkout()->finish($this->storedOrder(), 'PAYPAL-1');
            self::fail('An exception was expected.');
        } catch (PayPalException $exception) {
            self::assertSame('INSTRUMENT_DECLINED', $exception->getIssue());
        }
        self::assertTrue($this->storedOrder()->isOpen());
    }

    public function testNoSecondPaymentWhenTheFirstOneWasBookedUnnoticed()
    {
        $this->startPayment();
        $this->server->answer('GET', '/v2/checkout/orders/PAYPAL-1', 200, FakePayPalServer::capturedOrder('PAYPAL-1', 'COMPLETED', '19.90', 'EUR', (string)$this->order->getId()));

        try {
            $this->checkout()->start($this->storedOrder(), 'Kraft-Aufbau', 'Studio');
            self::fail('An exception was expected.');
        } catch (PayPalException $exception) {
            self::assertSame('ORDER_ALREADY_PAID', $exception->getIssue());
        }
        self::assertTrue($this->storedOrder()->isPaid(), 'The unnoticed payment is confirmed now.');
    }

    public function testSyncConfirmsAPaymentAfterALostConnection()
    {
        $this->startPayment();
        $this->server->answer('GET', '/v2/checkout/orders/PAYPAL-1', 200, FakePayPalServer::capturedOrder('PAYPAL-1', 'COMPLETED', '19.90', 'EUR', (string)$this->order->getId()));

        self::assertSame(PayPal::STATE_PAID, $this->checkout()->sync($this->storedOrder()));
        self::assertTrue($this->storedOrder()->isPaid());
    }

    public function testSyncTreatsAnExpiredPayPalOrderAsOpen()
    {
        $this->startPayment();
        $this->server->answer('GET', '/v2/checkout/orders/PAYPAL-1', 404, ['name' => 'RESOURCE_NOT_FOUND']);

        self::assertSame(PayPal::STATE_OPEN, $this->checkout()->sync($this->storedOrder()));
        self::assertTrue($this->storedOrder()->isOpen());
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
