<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Service;

/**
 * Stands in for the PayPal API in tests: answers requests with prepared answers and records them.
 */
class FakePayPalServer
{
    /**
     * Prepared answers: method, part of the URL, HTTP status, body.
     *
     * @var array<int, array{string, string, int, array}>
     */
    private array $answers = [];

    /**
     * Requests received: method, URL, headers, body.
     *
     * @var array<int, array{method: string, url: string, headers: string[], body: string|null}>
     */
    public array $requests = [];

    /**
     * Prepares the answer for the next request whose method and URL match.
     *
     * @param string $method
     * @param string $urlPart
     * @param int $status
     * @param array $body
     * @return $this
     */
    public function answer(string $method, string $urlPart, int $status, array $body): FakePayPalServer
    {
        $this->answers[] = [$method, $urlPart, $status, $body];
        return $this;
    }

    /**
     * Returns a PayPal client that talks to this fake server. The access token is always answered.
     *
     * @return PayPal
     */
    public function client(): PayPal
    {
        return new PayPal('client-id', 'secret', true, function (string $method, string $url, array $headers, ?string $body): array {
            $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];

            if (strpos($url, '/v1/oauth2/token') !== false) {
                return [200, json_encode(['access_token' => 'token-123', 'expires_in' => 32400])];
            }

            foreach ($this->answers as $index => [$answerMethod, $urlPart, $status, $answerBody]) {
                if ($answerMethod === $method && strpos($url, $urlPart) !== false) {
                    unset($this->answers[$index]);
                    return [$status, json_encode($answerBody)];
                }
            }

            return [500, json_encode(['name' => 'UNEXPECTED_REQUEST'])];
        });
    }

    /**
     * Requests except the access token.
     *
     * @return array
     */
    public function apiRequests(): array
    {
        return array_values(array_filter($this->requests, static fn ($request) => strpos($request['url'], '/v1/oauth2/token') === false));
    }

    /**
     * A PayPal order with a capture, like PayPal returns it.
     *
     * @param string $id
     * @param string $status status of the capture
     * @param string $value
     * @param string $currency
     * @param string $customId
     * @return array
     */
    public static function capturedOrder(string $id, string $status, string $value, string $currency, string $customId): array
    {
        return [
            'id' => $id,
            'status' => $status === 'COMPLETED' ? 'COMPLETED' : 'APPROVED',
            'purchase_units' => [[
                'custom_id' => $customId,
                'payments' => [
                    'captures' => [[
                        'id' => 'CAPTURE-' . $id,
                        'status' => $status,
                        'custom_id' => $customId,
                        'amount' => ['currency_code' => $currency, 'value' => $value],
                    ]],
                ],
            ]],
        ];
    }
}
