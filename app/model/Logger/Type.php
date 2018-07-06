<?php

declare(strict_types=1);

namespace Model\Logger\Log;

use Consistence\Enum\Enum;

class Type extends Enum
{
    public const OBJECT  = 'object';
    public const PAYMENT = 'payment';

    public function __toString() : string
    {
        return $this->getValue();
    }
}
