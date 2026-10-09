<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Service;

use Modules\Fitness\Models\PaymentOptions;
use PHPUnit\Framework\TestCase;

/**
 * Payment options and the placeholders of the order e-mails. No database needed.
 */
class PaymentTest extends TestCase
{
    public function testPaypalMeNameIsTakenFromNameOrLink()
    {
        self::assertSame('FitStudio', PaymentOptions::normalizePaypalMeName('FitStudio'));
        self::assertSame('FitStudio', PaymentOptions::normalizePaypalMeName(' https://www.paypal.me/FitStudio/ '));
        self::assertSame('FitStudio', PaymentOptions::normalizePaypalMeName('paypal.me/FitStudio'));
        self::assertSame('', PaymentOptions::normalizePaypalMeName('  '));
        self::assertNull(PaymentOptions::normalizePaypalMeName('Fit Studio'));
        self::assertNull(PaymentOptions::normalizePaypalMeName('evil.example/x'));
    }

    public function testPaypalMeLinkContainsAmountAndCurrency()
    {
        $options = new PaymentOptions(false, '', 'FitStudio', '');

        self::assertSame('https://paypal.me/FitStudio/29.90EUR', $options->getPaypalMeUrl('29.90', 'EUR'));
    }

    public function testBuyingNeedsAtLeastOneWayOfPayment()
    {
        self::assertFalse((new PaymentOptions(false, 'IBAN', '', ''))->isAvailable());
        self::assertFalse((new PaymentOptions(true, '', '', ''))->isAvailable(), 'Transfer without bank details is not offered.');
        self::assertTrue((new PaymentOptions(true, 'IBAN', '', ''))->isAvailable());
        self::assertTrue((new PaymentOptions(false, '', 'FitStudio', ''))->isAvailable());
    }

    public function testPlaceholdersAreReplacedOnlyOnce()
    {
        $text = OrderMails::fillPlaceholders(
            '<p>{name}: {reference} – {orderLink}</p>',
            ['{name}' => 'Anna {reference}', '{reference}' => 'FIT-ABC234'],
            ['{orderLink}' => '<a href="https://example.org">Link</a>']
        );

        self::assertSame('<p>Anna {reference}: FIT-ABC234 – <a href="https://example.org">Link</a></p>', $text);
    }

    public function testEveryTemplateExistsInBothLanguages()
    {
        foreach (OrderMails::TEMPLATES as $type => $locales) {
            self::assertSame(['de_DE', 'en_EN'], array_keys($locales), $type);
        }
    }
}
