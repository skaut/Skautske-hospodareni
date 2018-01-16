<?php

declare(strict_types=1);

namespace Model\Payment;

use Consistence\Enum\Enum;

final class EmailType extends Enum
{

    /**
     * This email type is used for sending payment info after payment was created
     */
    public const PAYMENT_INFO = 'payment_info';

}
