<?php

declare(strict_types=1);

namespace Model\Payment\Payment;

use Consistence\Enum\Enum;

/**
 * @method string getValue()
 */
class State extends Enum
{
    public const CANCELED  = 'canceled';
    public const COMPLETED = 'completed';
    public const PREPARING = 'preparing';

    public function toString() : string
    {
        return $this->getValue();
    }

    public function __toString() : string
    {
        return $this->getValue();
    }
}
