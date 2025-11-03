<?php

declare(strict_types=1);

namespace Model\Payment;

use Consistence\Enum\Enum;

/** @method string getValue() */
final class EmailType extends Enum
{
    /**
     * This email type is used for sending payment info after payment was created.
     */
    public const PAYMENT_INFO = 'payment_info';

    public const PAYMENT_COMPLETED = 'payment_completed';

    public const PAYMENT_CANCELED = 'payment_canceled';

    public const PAYMENT_REMINDER = 'payment_reminder';

    public function toString(): string
    {
        return $this->getValue();
    }
}
