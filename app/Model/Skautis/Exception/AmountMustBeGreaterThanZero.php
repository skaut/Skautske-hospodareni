<?php

declare(strict_types=1);

namespace App\Model\Skautis\Exception;

use Skautis\Wsdl\WsdlException;

final class AmountMustBeGreaterThanZero extends WsdlException
{
    public function __construct()
    {
        parent::__construct();
    }
}
