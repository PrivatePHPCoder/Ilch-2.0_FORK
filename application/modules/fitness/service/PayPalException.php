<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

/**
 * A request to PayPal failed. The issue is PayPal's error code, for example INSTRUMENT_DECLINED.
 */
class PayPalException extends \RuntimeException
{
    /**
     * @var string
     */
    private string $issue;

    public function __construct(string $message, string $issue = '', int $status = 0)
    {
        parent::__construct($message, $status);
        $this->issue = $issue;
    }

    public function getIssue(): string
    {
        return $this->issue;
    }
}
