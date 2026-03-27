<?php

declare(strict_types=1);

namespace App\Model\Common\Services;

interface QueryBus
{
    public function handle(object $query): mixed;
}
