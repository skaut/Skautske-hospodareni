<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Messenger\DI;

use Nette\DI\Definitions\Statement;

/**
 * Konfigurace jedné sběrnice (bus). Slouží jako schéma přes Nette\Schema\Expect::from().
 */
final class BusConfig
{
    public bool $allowNoHandlers = false;

    public bool $singleHandlerPerMessage = false;

    /** @var Statement[] */
    public array $middleware = [];
}
