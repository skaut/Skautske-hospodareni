<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Messenger;

/**
 * Popis jednoho handleru pro danou zprávu (služba v DI + metoda + options).
 */
final class HandlerDefinition
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public readonly string $serviceName,
        public readonly string $methodName,
        public readonly array $options,
    ) {
    }
}
