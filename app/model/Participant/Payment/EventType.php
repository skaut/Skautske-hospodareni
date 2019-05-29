<?php

declare(strict_types=1);

namespace Model\Participant\Payment;

use Consistence\Enum\Enum;

/**
 * @method string getValue()
 */
final class EventType extends Enum
{
    public const CAMP    = 'camp';
    public const GENERAL = 'general';

    public function toString() : string
    {
        return $this->getValue();
    }
}
