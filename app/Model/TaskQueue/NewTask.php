<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

final class NewTask
{
    /**
     * @param array<string, mixed> $payload  what the handler needs to run the task
     * @param string               $label    what the task is about in words, e.g. the payment name
     * @param string|null          $dedupKey the same key cannot wait in the queue twice
     */
    public function __construct(
        public readonly array $payload,
        public readonly string $label,
        public readonly ?string $dedupKey = null,
    ) {
    }
}
