<?php

declare(strict_types=1);

namespace Model\Skautis\Factory;

use Model\Event\Camp;

interface ICampFactory
{
    public function create(\stdClass $skautisCamp) : Camp;
}
