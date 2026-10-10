<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

/**
 * The ways of payment buyers are offered, read from the module settings.
 */
final class PaymentOptions
{
    /**
     * @var bool
     */
    private bool $transfer;

    /**
     * Account holder, IBAN and so on, as plain text.
     *
     * @var string
     */
    private string $bankDetails;

    /**
     * Name of the PayPal.Me link, empty if not offered.
     *
     * @var string
     */
    private string $paypalMeName;

    /**
     * Additional note on the payment page, for example how long the unlocking takes.
     *
     * @var string
     */
    private string $info;

    /**
     * Whether PayPal Checkout is switched on, where the server books the payment.
     *
     * @var bool
     */
    private bool $paypalCheckout;

    /**
     * Client id of the PayPal app. The secret is never kept in this object.
     *
     * @var string
     */
    private string $paypalClientId;

    /**
     * @var bool
     */
    private bool $paypalSandbox;

    /**
     * @var bool
     */
    private bool $paypalSecretStored;

    public function __construct(
        bool $transfer,
        string $bankDetails,
        string $paypalMeName,
        string $info,
        bool $paypalCheckout = false,
        string $paypalClientId = '',
        bool $paypalSandbox = true,
        bool $paypalSecretStored = false
    ) {
        $this->transfer = $transfer;
        $this->bankDetails = $bankDetails;
        $this->paypalMeName = $paypalMeName;
        $this->info = $info;
        $this->paypalCheckout = $paypalCheckout;
        $this->paypalClientId = $paypalClientId;
        $this->paypalSandbox = $paypalSandbox;
        $this->paypalSecretStored = $paypalSecretStored;
    }

    /**
     * @param \Ilch\Config\Database $config
     * @return PaymentOptions
     */
    public static function fromConfig($config): PaymentOptions
    {
        return new self(
            (bool)$config->get('fitness_payTransfer'),
            (string)$config->get('fitness_bankDetails'),
            (string)$config->get('fitness_payPaypalMe'),
            (string)$config->get('fitness_paymentInfo'),
            (bool)$config->get('fitness_paypalCheckout'),
            (string)$config->get('fitness_paypalClientId'),
            self::isSandboxSetting($config->get('fitness_paypalSandbox')),
            (string)$config->get('fitness_paypalSecret', true) !== ''
        );
    }

    /**
     * The sandbox is the default: only an explicit "0" means live payments.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isSandboxSetting($value): bool
    {
        return (string)$value !== '0';
    }

    public function isPaypalCheckoutSwitchedOn(): bool
    {
        return $this->paypalCheckout;
    }

    /**
     * Whether PayPal Checkout is switched on and has client id and secret.
     *
     * @return bool
     */
    public function isPaypalCheckoutEnabled(): bool
    {
        return $this->paypalCheckout && $this->paypalClientId !== '' && $this->paypalSecretStored;
    }

    public function getPaypalClientId(): string
    {
        return $this->paypalClientId;
    }

    public function isPaypalSandbox(): bool
    {
        return $this->paypalSandbox;
    }

    public function hasPaypalSecret(): bool
    {
        return $this->paypalSecretStored;
    }

    /**
     * URL of PayPal's JavaScript for the payment buttons.
     *
     * @param string $currency
     * @return string
     */
    public function getPaypalSdkUrl(string $currency): string
    {
        return 'https://www.paypal.com/sdk/js?' . http_build_query([
            'client-id' => $this->paypalClientId,
            'currency' => $currency,
            'intent' => 'capture',
            'components' => 'buttons',
        ]);
    }

    /**
     * Whether the transfer setting is turned on, even without bank details.
     *
     * @return bool
     */
    public function isTransferSwitchedOn(): bool
    {
        return $this->transfer;
    }

    public function isTransferEnabled(): bool
    {
        return $this->transfer && $this->bankDetails !== '';
    }

    public function getBankDetails(): string
    {
        return $this->bankDetails;
    }

    /**
     * Whether the PayPal.Me link is offered. With PayPal Checkout it is not, so buyers don't see
     * two PayPal buttons.
     *
     * @return bool
     */
    public function isPaypalMeEnabled(): bool
    {
        return $this->paypalMeName !== '' && !$this->isPaypalCheckoutEnabled();
    }

    public function getPaypalMeName(): string
    {
        return $this->paypalMeName;
    }

    /**
     * Returns the PayPal.Me link with the amount filled in, for example
     * https://paypal.me/name/29.90EUR.
     *
     * @param string $amount amount with a dot as decimal separator
     * @param string $currency
     * @return string
     */
    public function getPaypalMeUrl(string $amount, string $currency): string
    {
        return 'https://paypal.me/' . rawurlencode($this->paypalMeName) . '/' . rawurlencode($amount . $currency);
    }

    public function getInfo(): string
    {
        return $this->info;
    }

    /**
     * Whether at least one way of payment is offered. Without one, nothing can be bought.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->isTransferEnabled() || $this->isPaypalMeEnabled() || $this->isPaypalCheckoutEnabled();
    }

    /**
     * Turns the input of the settings into a PayPal.Me name. Accepts the name alone or a whole link.
     *
     * @param string $input
     * @return string|null the name, an empty string for no input, or null if the input is not valid
     */
    public static function normalizePaypalMeName(string $input): ?string
    {
        $name = trim($input);
        $name = preg_replace('~^(https?://)?(www\.)?paypal\.me/~i', '', $name);
        $name = rtrim($name, '/');

        if ($name === '') {
            return '';
        }

        return preg_match('/^[A-Za-z0-9]{1,50}$/', $name) ? $name : null;
    }
}
