<?php

declare(strict_types=1);

namespace Model\Skautis;

interface ISkautisEvent
{
    public function getUnitId() : int;
}
