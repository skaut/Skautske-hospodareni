<?php

declare(strict_types=1);

namespace Model\Skautis\Exception;

use Exception;

final class MissingCurrentRole extends Exception
{
    public function __construct()
    {
        parent::__construct();
    }
}
