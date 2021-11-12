<?php

declare(strict_types=1);

namespace Model\Common\Services;

interface CommandBus
{
    public function handle(object $command): void;
}
