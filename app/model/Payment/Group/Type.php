<?php

declare(strict_types=1);

namespace Model\Payment\Group;

use Consistence\Enum\Enum;

class Type extends Enum
{
    public const CAMP         = 'camp';
    public const REGISTRATION = 'registration';

    public function __toString() : string
    {
        return $this->getValue();
    }
}
