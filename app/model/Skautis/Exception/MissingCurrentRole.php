<?php

declare(strict_types=1);

namespace Model\Skautis\Exception;

use Exception;

final class MissingCurrentRole extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
