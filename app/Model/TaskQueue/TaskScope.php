<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

/**
 * Whose batch it is, e.g. one payment group. Progress is shown and kept apart per scope.
 */
final class TaskScope
{
    public const PAYMENT_GROUP = 'payment_group';

    public function __construct(public readonly string $type, public readonly int $id)
    {
    }

    public static function paymentGroup(int $groupId): self
    {
        return new self(self::PAYMENT_GROUP, $groupId);
    }
}
