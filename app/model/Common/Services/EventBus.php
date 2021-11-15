<?php

declare(strict_types=1);

namespace Model\Common\Services;

interface EventBus
{
    public function handle(object $event): void;
}
