<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Mail of Ilch with PHPMailer in exception mode, so a failed delivery ends in an exception.
 * OrderMails catches it and can tell the admin that the e-mail was not sent.
 */
class Mail extends \Ilch\Mail
{
    public function PHPMailer()
    {
        return new PHPMailer(true);
    }
}
