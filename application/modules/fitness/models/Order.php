<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Models;

use Ilch\Model;

/**
 * An order of a paid program. The price is stored when the order is placed, so later price
 * changes don't touch existing orders.
 */
class Order extends Model
{
    public const STATUS_OPEN = 0;
    public const STATUS_PAID = 1;
    public const STATUS_CANCELLED = 2;
    public const STATUS_REFUNDED = 3;

    /**
     * Translation keys of the states.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_OPEN => 'orderOpen',
        self::STATUS_PAID => 'orderPaid',
        self::STATUS_CANCELLED => 'orderCancelled',
        self::STATUS_REFUNDED => 'orderRefunded',
    ];

    /**
     * Names of the states for URLs. Ilch drops the value 0 when building URLs, so the number
     * of the open state can't be used there.
     *
     * @var array<int, string>
     */
    public const STATUS_NAMES = [
        self::STATUS_OPEN => 'open',
        self::STATUS_PAID => 'paid',
        self::STATUS_CANCELLED => 'cancelled',
        self::STATUS_REFUNDED => 'refunded',
    ];

    /**
     * Ways of payment an admin can choose when confirming, with their translation keys.
     *
     * @var array<string, string>
     */
    public const METHODS = [
        'transfer' => 'paymentTransfer',
        'paypalme' => 'paymentPaypalMe',
        'other' => 'paymentOther',
    ];

    /**
     * @var int
     */
    protected int $id = 0;

    /**
     * Null if the user account was deleted.
     *
     * @var int|null
     */
    protected ?int $userId = null;

    /**
     * Name of the user. Only filled when loaded by the mapper.
     *
     * @var string
     */
    protected string $userName = '';

    /**
     * E-mail address of the user. Only filled when loaded by the mapper.
     *
     * @var string
     */
    protected string $userEmail = '';

    /**
     * @var int
     */
    protected int $programId = 0;

    /**
     * Title of the program. Only filled when loaded by the mapper.
     *
     * @var string
     */
    protected string $programTitle = '';

    /**
     * Amount with two decimals, for example "29.90".
     *
     * @var string
     */
    protected string $amount = '0.00';

    /**
     * @var string
     */
    protected string $currency = 'EUR';

    /**
     * @var int
     */
    protected int $status = self::STATUS_OPEN;

    /**
     * One of the keys of METHODS, empty until the payment is confirmed.
     *
     * @var string
     */
    protected string $paymentMethod = '';

    /**
     * Code the buyer puts into the payment reference, for example "FIT-7K3M9Q".
     *
     * @var string
     */
    protected string $referenceCode = '';

    /**
     * @var string
     */
    protected string $createdAt = '';

    /**
     * @var string|null
     */
    protected ?string $paidAt = null;

    /**
     * Admin who confirmed the payment.
     *
     * @var int|null
     */
    protected ?int $confirmedBy = null;

    /**
     * Internal note of the admins.
     *
     * @var string
     */
    protected string $note = '';

    /**
     * @param array $entries
     * @return $this
     */
    public function setByArray(array $entries): Order
    {
        if (isset($entries['id'])) {
            $this->setId($entries['id']);
        }
        if (array_key_exists('user_id', $entries)) {
            $this->setUserId(WorkoutExercise::toNullableInt($entries['user_id']));
        }
        if (isset($entries['user_name'])) {
            $this->setUserName($entries['user_name']);
        }
        if (isset($entries['user_email'])) {
            $this->setUserEmail($entries['user_email']);
        }
        if (isset($entries['program_id'])) {
            $this->setProgramId($entries['program_id']);
        }
        if (isset($entries['program_title'])) {
            $this->setProgramTitle($entries['program_title']);
        }
        if (isset($entries['amount'])) {
            $this->setAmount($entries['amount']);
        }
        if (isset($entries['currency'])) {
            $this->setCurrency($entries['currency']);
        }
        if (isset($entries['status'])) {
            $this->setStatus($entries['status']);
        }
        if (isset($entries['payment_method'])) {
            $this->setPaymentMethod($entries['payment_method']);
        }
        if (isset($entries['reference_code'])) {
            $this->setReferenceCode($entries['reference_code']);
        }
        if (isset($entries['created_at'])) {
            $this->setCreatedAt($entries['created_at']);
        }
        if (array_key_exists('paid_at', $entries)) {
            $this->setPaidAt($entries['paid_at']);
        }
        if (array_key_exists('confirmed_by', $entries)) {
            $this->setConfirmedBy(WorkoutExercise::toNullableInt($entries['confirmed_by']));
        }
        if (isset($entries['note'])) {
            $this->setNote($entries['note']);
        }

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Order
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): Order
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): Order
    {
        $this->userName = $userName;
        return $this;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): Order
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    public function getProgramId(): int
    {
        return $this->programId;
    }

    public function setProgramId(int $programId): Order
    {
        $this->programId = $programId;
        return $this;
    }

    public function getProgramTitle(): string
    {
        return $this->programTitle;
    }

    public function setProgramTitle(string $programTitle): Order
    {
        $this->programTitle = $programTitle;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Stores the amount with two decimals.
     *
     * @param string $amount
     * @return $this
     */
    public function setAmount(string $amount): Order
    {
        $this->amount = is_numeric($amount) && (float)$amount >= 0 ? number_format((float)$amount, 2, '.', '') : '0.00';
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): Order
    {
        $currency = strtoupper(trim($currency));
        $this->currency = preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'EUR';
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): Order
    {
        $this->status = isset(self::STATUSES[$status]) ? $status : self::STATUS_OPEN;
        return $this;
    }

    public function getStatusKey(): string
    {
        return self::STATUSES[$this->status];
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod one of the keys of METHODS or an empty string
     * @return $this
     */
    public function setPaymentMethod(string $paymentMethod): Order
    {
        $this->paymentMethod = isset(self::METHODS[$paymentMethod]) ? $paymentMethod : '';
        return $this;
    }

    public function getPaymentMethodKey(): string
    {
        return self::METHODS[$this->paymentMethod] ?? '';
    }

    public function getReferenceCode(): string
    {
        return $this->referenceCode;
    }

    public function setReferenceCode(string $referenceCode): Order
    {
        $this->referenceCode = $referenceCode;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): Order
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getPaidAt(): ?string
    {
        return $this->paidAt;
    }

    public function setPaidAt(?string $paidAt): Order
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getConfirmedBy(): ?int
    {
        return $this->confirmedBy;
    }

    public function setConfirmedBy(?int $confirmedBy): Order
    {
        $this->confirmedBy = $confirmedBy;
        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): Order
    {
        $this->note = $note;
        return $this;
    }

    /**
     * Returns the fields of the orders table.
     *
     * @param bool $withId
     * @return array
     */
    public function getArray(bool $withId = true): array
    {
        return array_merge(
            ($withId ? ['id' => $this->getId()] : []),
            [
                'user_id' => $this->getUserId(),
                'program_id' => $this->getProgramId(),
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency(),
                'status' => $this->getStatus(),
                'payment_method' => $this->getPaymentMethod(),
                'reference_code' => $this->getReferenceCode(),
                'created_at' => $this->getCreatedAt(),
                'paid_at' => $this->getPaidAt(),
                'confirmed_by' => $this->getConfirmedBy(),
                'note' => $this->getNote(),
            ]
        );
    }
}
