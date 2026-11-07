<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

/** A task one run took over, identified by its claim token. */
final class ClaimedTask
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly int $itemId,
        public readonly int $jobId,
        public readonly string $type,
        public readonly array $payload,
        public readonly ?string $createdByName,
        public readonly int $attempts,
        public readonly string $token,
    ) {
    }
}
