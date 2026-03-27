<?php

declare(strict_types=1);

namespace Hskauting\Tests;

use App\Model\Common\Services\EventBus;

final class NullEventBus implements EventBus
{
    public function handle(object $event): void
    {
        // Do nothing
    }
}
