<?php

declare(strict_types=1);

namespace Model\Skautis;

use Skautis\Skautis;

class SkautisMaintenanceChecker
{
    /** @var Skautis */
    private $skautis;

    public function __construct(Skautis $skautis)
    {
        $this->skautis = $skautis;
    }

    public function isMaintenance() : bool
    {
        return $this->skautis->isMaintenance();
    }
}
