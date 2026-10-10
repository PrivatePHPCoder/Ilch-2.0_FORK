<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use Ilch\Date;
use Ilch\Design\Base as Design;
use Ilch\Registry;
use Modules\Admin\Mappers\Emails as EmailsMapper;
use Modules\Fitness\Models\Order as OrderModel;
use Modules\User\Mappers\Notifications as NotificationsMapper;
use Modules\User\Mappers\User as UserMapper;
use Modules\User\Models\Notification as NotificationModel;

/**
 * E-mails about orders. The texts are e-mail templates of Ilch, so admins can change them in the
 * admin area under "E-Mails".
 */
class OrderMails
{
    public const TYPE_CREATED = 'order_created';
    public const TYPE_PAID = 'order_paid';
    public const TYPE_ADMIN = 'order_admin';

    /**
     * Default templates per type and language. Placeholders: {name}, {program}, {amount},
     * {reference}, {sitetitle}, {orderLink} and {programLink}.
     *
     * @var array<string, array<string, array{desc: string, text: string}>>
     */
    public const TEMPLATES = [
        self::TYPE_CREATED => [
            'de_DE' => [
                'desc' => 'Deine Bestellung {reference}',
                'text' => '<p>Hallo {name},</p>
<p>vielen Dank für deine Bestellung von <b>{program}</b>.</p>
<p>Betrag: <b>{amount}</b><br>Verwendungszweck: <b>{reference}</b></p>
<p>Wie du bezahlen kannst, siehst du hier: {orderLink}</p>
<p>Sobald deine Zahlung eingegangen ist, schalten wir das Programm für dich frei. Darüber bekommst du noch eine E-Mail.</p>
<p>Viele Grüße<br>{sitetitle}</p>',
            ],
            'en_EN' => [
                'desc' => 'Your order {reference}',
                'text' => '<p>Hello {name},</p>
<p>thank you for your order of <b>{program}</b>.</p>
<p>Amount: <b>{amount}</b><br>Payment reference: <b>{reference}</b></p>
<p>You can see how to pay here: {orderLink}</p>
<p>As soon as your payment has arrived, we unlock the program for you. You will get another e-mail about it.</p>
<p>Best regards<br>{sitetitle}</p>',
            ],
        ],
        self::TYPE_PAID => [
            'de_DE' => [
                'desc' => 'Zahlung erhalten: {program}',
                'text' => '<p>Hallo {name},</p>
<p>deine Zahlung für <b>{program}</b> ist eingegangen. Das Programm ist jetzt für dich freigeschaltet.</p>
<p>Hier geht es los: {programLink}</p>
<p>Viel Erfolg beim Training!<br>{sitetitle}</p>',
            ],
            'en_EN' => [
                'desc' => 'Payment received: {program}',
                'text' => '<p>Hello {name},</p>
<p>your payment for <b>{program}</b> has arrived. The program is now unlocked for you.</p>
<p>Get started here: {programLink}</p>
<p>Enjoy your training!<br>{sitetitle}</p>',
            ],
        ],
        self::TYPE_ADMIN => [
            'de_DE' => [
                'desc' => 'Neue Bestellung {reference}',
                'text' => '<p>Es gibt eine neue Bestellung.</p>
<p>Programm: <b>{program}</b><br>Benutzer: <b>{name}</b><br>Betrag: <b>{amount}</b><br>Verwendungszweck: <b>{reference}</b></p>
<p>Zur Bestellung im Adminbereich: {orderLink}</p>',
            ],
            'en_EN' => [
                'desc' => 'New order {reference}',
                'text' => '<p>There is a new order.</p>
<p>Program: <b>{program}</b><br>User: <b>{name}</b><br>Amount: <b>{amount}</b><br>Payment reference: <b>{reference}</b></p>
<p>Open the order in the admin area: {orderLink}</p>',
            ],
        ],
    ];

    /**
     * @var Design
     */
    private Design $design;

    /**
     * @var EmailsMapper
     */
    private EmailsMapper $emailsMapper;

    /**
     * @param Design $design layout or view, used to escape and purify
     * @param EmailsMapper|null $emailsMapper
     */
    public function __construct(Design $design, ?EmailsMapper $emailsMapper = null)
    {
        $this->design = $design;
        $this->emailsMapper = $emailsMapper ?? new EmailsMapper();
    }

    /**
     * Tells the buyer that the payment arrived and the program is unlocked: as notification of the
     * website and by e-mail.
     *
     * @param OrderModel $order
     * @param string $programUrl full URL of the program page
     * @return bool whether the e-mail was sent
     */
    public function sendPaymentConfirmation(OrderModel $order, string $programUrl): bool
    {
        $user = $order->getUserId() !== null ? (new UserMapper())->getUserById($order->getUserId()) : null;
        if (!$user) {
            return false;
        }

        $message = $this->design->getTranslator()->trans('orderPaidNotification', $order->getProgramTitle());
        (new NotificationsMapper())->addNotification((new NotificationModel())
            ->setUserId($user->getId())
            ->setModule('fitness')
            ->setMessage(mb_substr($message, 0, 255))
            ->setURL($programUrl)
            ->setType('orderPaid'));

        return $this->sendForOrder(
            self::TYPE_PAID,
            $order,
            $user->getEmail(),
            $user->getName(),
            $user->getLocale() ?: (string)Registry::get('config')->get('locale'),
            ['{programLink}' => $programUrl]
        );
    }

    /**
     * Sends one of the order e-mails with the placeholders of the order filled in.
     *
     * @param string $type one of the TYPE_* constants
     * @param OrderModel $order
     * @param string $toEmail
     * @param string $toName
     * @param string $locale
     * @param array<string, string> $links placeholder => URL, for example ['{orderLink}' => ...]
     * @return bool
     */
    public function sendForOrder(string $type, OrderModel $order, string $toEmail, string $toName, string $locale, array $links): bool
    {
        return $this->send($type, $toEmail, $toName, $locale, [
            '{name}' => $order->getUserName(),
            '{program}' => $order->getProgramTitle(),
            '{amount}' => $this->design->getFormattedCurrency((float)$order->getAmount(), $order->getCurrency()),
            '{reference}' => $order->getReferenceCode(),
        ], $links);
    }

    /**
     * Sends an e-mail. A failing mail server doesn't stop the order, it only returns false.
     *
     * @param string $type one of the TYPE_* constants
     * @param string $toEmail
     * @param string $toName
     * @param string $locale language of the template, for example "de_DE"
     * @param array<string, string> $texts placeholder => plain text, gets escaped
     * @param array<string, string> $links placeholder => URL, becomes a link
     * @return bool
     */
    public function send(string $type, string $toEmail, string $toName, string $locale, array $texts, array $links = []): bool
    {
        $template = $this->emailsMapper->getEmail('fitness', $type, $locale);
        if (!$template || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $config = Registry::get('config');
        $siteTitle = (string)$config->get('page_title');
        $texts['{sitetitle}'] = $siteTitle;

        $html = [];
        foreach ($links as $placeholder => $url) {
            $html[$placeholder] = '<a href="' . $this->design->escape($url) . '">' . $this->design->escape($url) . '</a>';
        }

        $content = self::fillPlaceholders($this->design->purify($template->getText()), $this->escapeAll($texts), $html);
        $message = self::fillPlaceholders($this->getWrapper(), $this->escapeAll([
            '{sitetitle}' => $siteTitle,
            '{date}' => (new Date())->format('d.m.Y', true),
            '{footer}' => $this->design->getTranslator()->trans('mailFooter', $siteTitle),
        ]), ['{content}' => $content]);

        try {
            (new Mail())
                ->setFromName($siteTitle)
                ->setFromEmail($config->get('standardMail'))
                ->setToName($toName)
                ->setToEmail($toEmail)
                ->setSubject(self::fillPlaceholders($template->getDesc(), $texts))
                ->setMessage($message)
                ->send();
        } catch (\Throwable $exception) {
            return false;
        }

        return true;
    }

    /**
     * Replaces placeholders. Text values have to be escaped already if the target is HTML.
     *
     * @param string $template
     * @param array<string, string> $texts
     * @param array<string, string> $html placeholder => ready HTML, replaced first
     * @return string
     */
    public static function fillPlaceholders(string $template, array $texts, array $html = []): string
    {
        $values = $html + $texts;

        // strtr() replaces every placeholder once, so a value can't bring in a new placeholder.
        return strtr($template, $values);
    }

    /**
     * @param array<string, string> $texts
     * @return array<string, string>
     */
    private function escapeAll(array $texts): array
    {
        return array_map(fn ($text) => $this->design->escape((string)$text), $texts);
    }

    /**
     * Returns the HTML frame of the e-mails. A layout can bring its own file under
     * views/modules/fitness/layouts/mail/order.php.
     *
     * @return string
     */
    private function getWrapper(): string
    {
        $layoutFile = APPLICATION_PATH . '/layouts/' . Registry::get('config')->get('default_layout') . '/views/modules/fitness/layouts/mail/order.php';

        return (string)file_get_contents(is_file($layoutFile) ? $layoutFile : APPLICATION_PATH . '/modules/fitness/layouts/mail/order.php');
    }
}
