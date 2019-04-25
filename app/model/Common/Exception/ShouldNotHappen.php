<?php

declare(strict_types=1);

namespace Model\Common;

use RuntimeException;

/**
 * This exception shouldn't be catched as it means that there is logical error in app (e.g. unexpected enum value)
 */
class ShouldNotHappen extends RuntimeException
{
    public function __construct(string $message = 'Internal error')
    {
        parent::__construct($message);
    }
}
