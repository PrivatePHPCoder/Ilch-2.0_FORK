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

    public function __construct(bool $transfer, string $bankDetails, string $paypalMeName, string $info)
    {
        $this->transfer = $transfer;
        $this->bankDetails = $bankDetails;
        $this->paypalMeName = $paypalMeName;
        $this->info = $info;
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
            (string)$config->get('fitness_paymentInfo')
        );
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

    public function isPaypalMeEnabled(): bool
    {
        return $this->paypalMeName !== '';
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
        return $this->isTransferEnabled() || $this->isPaypalMeEnabled();
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
