<?php

declare(strict_types=1);

namespace App\Model\Common\Services;

interface CommandBus
{
    public function handle(object $command): void;
}
