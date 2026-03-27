<?php

declare(strict_types=1);

namespace App\Model\Common\Services;

interface EventBus
{
    public function handle(object $event): void;
}
