<?php

declare(strict_types=1);

namespace Model\Skautis\Exception;

use Skautis\Wsdl\WsdlException;

final class AmountMustBeGreaterThanZero extends WsdlException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
