<?php

declare(strict_types=1);

namespace Model\Skautis\Exception;

use Skautis\Wsdl\WsdlException;
use Throwable;

final class AmountMustBeGreaterThanZero extends WsdlException
{
    public function __construct(string $message = '', int $code = 0, Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
