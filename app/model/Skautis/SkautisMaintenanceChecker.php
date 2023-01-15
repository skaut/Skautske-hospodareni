<?php

declare(strict_types=1);

namespace Model\Skautis;

use Skautis\Skautis;

class SkautisMaintenanceChecker
{
    public function __construct(private Skautis $skautis)
    {
    }

    public function isMaintenance(): bool
    {
        return $this->skautis->isMaintenance();
    }
}
