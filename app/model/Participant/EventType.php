<?php

declare(strict_types=1);

namespace Model\Participant;

use Consistence\Enum\Enum;

/**
 * @method string getValue()
 */
final class EventType extends Enum
{
    public const GENERAL = 'general';
    public const CAMP    = 'camp';

    public function toString() : string
    {
        return $this->getValue();
    }
}
