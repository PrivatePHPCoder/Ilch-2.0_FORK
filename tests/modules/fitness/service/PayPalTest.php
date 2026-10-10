<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Models\Order as OrderModel;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/FakePayPalServer.php';

/**
 * The PayPal client and the check of PayPal's answers. No network, no database.
 */
class PayPalTest extends TestCase
{
    private function order(): OrderModel
    {
        return (new OrderModel())->setId(7)->setAmount('19.90')->setCurrency('EUR')->setReferenceCode('FIT-ABC234');
    }

    public function testOrderIsCreatedWithAmountAndIdsOfTheOwnOrder()
    {
        $server = (new FakePayPalServer())->answer('POST', '/v2/checkout/orders', 201, ['id' => 'PAYPAL-1', 'status' => 'CREATED']);

        $id = $server->client()->createOrder($this->order(), 'Kraft-Aufbau', 'Mein Studio');

        self::assertSame('PAYPAL-1', $id);
        [$token, $create] = $server->requests;
        self::assertStringStartsWith(PayPal::URL_SANDBOX, $token['url']);
        self::assertContains('Authorization: Basic ' . base64_encode('client-id:secret'), $token['headers']);
        self::assertSame('grant_type=client_credentials', $token['body']);
        self::assertContains('Authorization: Bearer token-123', $create['headers']);

        $unit = json_decode($create['body'], true)['purchase_units'][0];
        self::assertSame('CAPTURE', json_decode($create['body'], true)['intent']);
        self::assertSame(['currency_code' => 'EUR', 'value' => '19.90'], $unit['amount']);
        self::assertSame('7', $unit['custom_id']);
        self::assertSame('FIT-ABC234', $unit['invoice_id']);
    }

    public function testAccessTokenIsRequestedOnce()
    {
        $server = (new FakePayPalServer())
            ->answer('GET', '/v2/checkout/orders/A', 200, ['id' => 'A'])
            ->answer('GET', '/v2/checkout/orders/B', 200, ['id' => 'B']);
        $client = $server->client();

        $client->getOrder('A');
        $client->getOrder('B');

        self::assertCount(3, $server->requests);
    }

    public function testErrorsKeepPayPalsIssue()
    {
        $server = (new FakePayPalServer())->answer('POST', '/capture', 422, [
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [['issue' => 'INSTRUMENT_DECLINED']],
        ]);

        try {
            $server->client()->captureOrder('PAYPAL-1');
            self::fail('An exception was expected.');
        } catch (PayPalException $exception) {
            self::assertSame('INSTRUMENT_DECLINED', $exception->getIssue());
            self::assertSame(422, $exception->getCode());
        }
    }

    public function testWrongCredentialsAreDetected()
    {
        $client = new PayPal('id', 'wrong', true, static fn () => [401, json_encode(['error' => 'invalid_client'])]);

        self::assertFalse($client->testCredentials());
    }

    public function testOnlyACompletedCaptureOfExactlyThisOrderCountsAsPaid()
    {
        $order = $this->order();
        $evaluate = static fn (array $paypalOrder) => PayPal::evaluate($paypalOrder, $order)['state'];

        self::assertSame(PayPal::STATE_PAID, $evaluate(FakePayPalServer::capturedOrder('P', 'COMPLETED', '19.90', 'EUR', '7')));
        self::assertSame(PayPal::STATE_PAID, $evaluate(FakePayPalServer::capturedOrder('P', 'COMPLETED', '19.9', 'EUR', '7')));
        self::assertSame(PayPal::STATE_PENDING, $evaluate(FakePayPalServer::capturedOrder('P', 'PENDING', '19.90', 'EUR', '7')));
        self::assertSame(PayPal::STATE_FAILED, $evaluate(FakePayPalServer::capturedOrder('P', 'DECLINED', '19.90', 'EUR', '7')));
        self::assertSame(PayPal::STATE_INVALID, $evaluate(FakePayPalServer::capturedOrder('P', 'COMPLETED', '1.00', 'EUR', '7')), 'Wrong amount');
        self::assertSame(PayPal::STATE_INVALID, $evaluate(FakePayPalServer::capturedOrder('P', 'COMPLETED', '19.90', 'USD', '7')), 'Wrong currency');
        self::assertSame(PayPal::STATE_INVALID, $evaluate(FakePayPalServer::capturedOrder('P', 'COMPLETED', '19.90', 'EUR', '8')), 'Other order');
        self::assertSame(PayPal::STATE_OPEN, $evaluate(['id' => 'P', 'status' => 'APPROVED', 'purchase_units' => [['custom_id' => '7']]]), 'Approved, but not captured yet');
    }

    public function testClientNeedsCompleteSettings()
    {
        $config = static fn (array $values) => new class ($values) {
            private array $values;

            public function __construct(array $values)
            {
                $this->values = $values;
            }

            public function get(string $key, bool $alwaysLoad = false)
            {
                return $this->values[$key] ?? null;
            }
        };
        $complete = ['fitness_paypalCheckout' => '1', 'fitness_paypalClientId' => 'id', 'fitness_paypalSecret' => 'secret', 'fitness_paypalSandbox' => '1'];

        self::assertNotNull(PayPal::fromConfig($config($complete)));
        self::assertTrue(PayPal::fromConfig($config($complete))->isSandbox());
        self::assertNull(PayPal::fromConfig($config(['fitness_paypalCheckout' => '0'] + $complete)));
        self::assertNull(PayPal::fromConfig($config(['fitness_paypalSecret' => ''] + $complete)));
    }
}
