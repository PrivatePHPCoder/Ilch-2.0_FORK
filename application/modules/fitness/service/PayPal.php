<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Models\Order as OrderModel;
use Modules\Fitness\Models\PaymentOptions;

/**
 * Small client for the PayPal Orders API v2. Amount and currency always come from the own order,
 * never from the browser.
 */
class PayPal
{
    public const URL_LIVE = 'https://api-m.paypal.com';
    public const URL_SANDBOX = 'https://api-m.sandbox.paypal.com';

    public const STATE_PAID = 'paid';
    public const STATE_PENDING = 'pending';
    public const STATE_OPEN = 'open';
    public const STATE_FAILED = 'failed';
    public const STATE_INVALID = 'invalid';

    /**
     * @var string
     */
    private string $clientId;

    /**
     * @var string
     */
    private string $secret;

    /**
     * @var bool
     */
    private bool $sandbox;

    /**
     * Sends a request: function (string $method, string $url, array $headers, ?string $body): array{int, string}
     * returns the HTTP status and the body.
     *
     * @var callable
     */
    private $transport;

    /**
     * @var string|null
     */
    private ?string $accessToken = null;

    public function __construct(string $clientId, string $secret, bool $sandbox, ?callable $transport = null)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->sandbox = $sandbox;
        $this->transport = $transport ?? [self::class, 'curlTransport'];
    }

    /**
     * Returns the client of the module settings, or null if PayPal Checkout is not set up.
     *
     * @param \Ilch\Config\Database $config
     * @return PayPal|null
     */
    public static function fromConfig($config): ?PayPal
    {
        $clientId = (string)$config->get('fitness_paypalClientId');
        $secret = (string)$config->get('fitness_paypalSecret', true);

        if (!$config->get('fitness_paypalCheckout') || $clientId === '' || $secret === '') {
            return null;
        }

        return new self($clientId, $secret, PaymentOptions::isSandboxSetting($config->get('fitness_paypalSandbox')));
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Checks whether PayPal accepts the client id and secret.
     *
     * @return bool
     */
    public function testCredentials(): bool
    {
        try {
            $this->getAccessToken();
        } catch (PayPalException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Creates a PayPal order for the own order.
     *
     * @param OrderModel $order
     * @param string $description shown to the buyer at PayPal, for example the program title
     * @param string $brandName name of the shop at PayPal, for example the title of the website
     * @return string id of the PayPal order
     */
    public function createOrder(OrderModel $order, string $description, string $brandName): string
    {
        $result = $this->request('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order->getReferenceCode(),
                'invoice_id' => $order->getReferenceCode(),
                'custom_id' => (string)$order->getId(),
                'description' => mb_substr($description, 0, 127),
                'amount' => [
                    'currency_code' => $order->getCurrency(),
                    'value' => $order->getAmount(),
                ],
            ]],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'brand_name' => mb_substr($brandName, 0, 127),
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'PAY_NOW',
                    ],
                ],
            ],
        ]);

        if (empty($result['id']) || !is_string($result['id'])) {
            throw new PayPalException('PayPal returned no order id.');
        }

        return $result['id'];
    }

    /**
     * Captures the payment of an approved PayPal order.
     *
     * @param string $paypalOrderId
     * @return array the PayPal order after capturing
     */
    public function captureOrder(string $paypalOrderId): array
    {
        return $this->request('POST', '/v2/checkout/orders/' . rawurlencode($paypalOrderId) . '/capture', []);
    }

    /**
     * @param string $paypalOrderId
     * @return array the PayPal order
     */
    public function getOrder(string $paypalOrderId): array
    {
        return $this->request('GET', '/v2/checkout/orders/' . rawurlencode($paypalOrderId));
    }

    /**
     * Checks a PayPal order against the own order: the capture must belong to this order and have
     * exactly its amount and currency.
     *
     * @param array $paypalOrder
     * @param OrderModel $order
     * @return array{state: string, captureId: string|null} state is one of the STATE_* constants
     */
    public static function evaluate(array $paypalOrder, OrderModel $order): array
    {
        $unit = $paypalOrder['purchase_units'][0] ?? [];
        $capture = $unit['payments']['captures'][0] ?? null;

        if (!is_array($capture)) {
            return ['state' => self::STATE_OPEN, 'captureId' => null];
        }

        $captureId = isset($capture['id']) ? (string)$capture['id'] : null;
        $customId = (string)($capture['custom_id'] ?? $unit['custom_id'] ?? '');
        $value = (string)($capture['amount']['value'] ?? '');
        $currency = (string)($capture['amount']['currency_code'] ?? '');

        if (
            $customId !== (string)$order->getId()
            || !is_numeric($value)
            || number_format((float)$value, 2, '.', '') !== $order->getAmount()
            || $currency !== $order->getCurrency()
        ) {
            return ['state' => self::STATE_INVALID, 'captureId' => $captureId];
        }

        switch ($capture['status'] ?? '') {
            case 'COMPLETED':
                return ['state' => self::STATE_PAID, 'captureId' => $captureId];
            case 'PENDING':
                return ['state' => self::STATE_PENDING, 'captureId' => $captureId];
            default:
                return ['state' => self::STATE_FAILED, 'captureId' => $captureId];
        }
    }

    /**
     * Sends an authorized request and returns the decoded answer.
     *
     * @param string $method
     * @param string $path
     * @param array|null $body
     * @return array
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        [$status, $answer] = ($this->transport)(
            $method,
            $this->getBaseUrl() . $path,
            [
                'Authorization: Bearer ' . $this->getAccessToken(),
                'Content-Type: application/json',
                'Prefer: return=representation',
            ],
            $body === null ? null : json_encode($body === [] ? new \stdClass() : $body)
        );

        return self::decode($status, $answer);
    }

    /**
     * Gets an access token with client id and secret, once per instance.
     *
     * @return string
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        [$status, $answer] = ($this->transport)(
            'POST',
            $this->getBaseUrl() . '/v1/oauth2/token',
            [
                'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->secret),
                'Content-Type: application/x-www-form-urlencoded',
            ],
            'grant_type=client_credentials'
        );
        $result = self::decode($status, $answer);

        if (empty($result['access_token']) || !is_string($result['access_token'])) {
            throw new PayPalException('PayPal returned no access token.');
        }

        return $this->accessToken = $result['access_token'];
    }

    private function getBaseUrl(): string
    {
        return $this->sandbox ? self::URL_SANDBOX : self::URL_LIVE;
    }

    /**
     * Decodes an answer of PayPal. Errors become a PayPalException with PayPal's error code.
     *
     * @param int $status
     * @param string $answer
     * @return array
     */
    private static function decode(int $status, string $answer): array
    {
        $result = json_decode($answer, true);

        if ($status >= 200 && $status < 300 && is_array($result)) {
            return $result;
        }

        $issue = is_array($result) ? (string)($result['details'][0]['issue'] ?? $result['name'] ?? $result['error'] ?? '') : '';

        throw new PayPalException('PayPal answered with status ' . $status . ($issue !== '' ? ' (' . $issue . ')' : '') . '.', $issue, $status);
    }

    /**
     * Sends a request with cURL and the certificates that come with Ilch.
     *
     * @param string $method
     * @param string $url
     * @param string[] $headers
     * @param string|null $body
     * @return array{int, string}
     */
    private static function curlTransport(string $method, string $url, array $headers, ?string $body): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => ROOT_PATH . '/certificate/cacert.pem',
        ]);
        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $answer = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        unset($curl);

        if ($answer === false) {
            throw new PayPalException('PayPal could not be reached: ' . $error);
        }

        return [$status, (string)$answer];
    }
}
