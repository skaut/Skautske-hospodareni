<?php

declare(strict_types=1);

namespace App\Model\DTO\Google;

use DateTimeImmutable;

/**
 * A connected Google account that Google refuses, so e-mails cannot be sent from it, as one user sees it.
 */
final class BrokenSenderAccount
{
    /**
     * @param string $unitName     unit the account belongs to; it is connected again under a role of that unit
     * @param bool   $canReconnect the active role of the user belongs to the unit of the account and may edit it
     */
    public function __construct(
        public readonly string $oAuthId,
        public readonly string $email,
        public readonly int $unitId,
        public readonly string $unitName,
        public readonly DateTimeImmutable $invalidSince,
        public readonly bool $canReconnect,
    ) {
    }
}
