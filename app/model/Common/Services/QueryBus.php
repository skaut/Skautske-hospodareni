<?php

declare(strict_types=1);

namespace Model\Common\Services;

interface QueryBus
{
    public function handle(object $query): mixed;
}
