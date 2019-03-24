<?php

declare(strict_types=1);

namespace Model\Payment;

use function implode;
use function sprintf;

final class NoAccessToBankAccount extends \Exception
{
    /**
     * @param int[] $unitIds
     */
    public static function forUnits(array $unitIds, int $bankAccountId) : self
    {
        return new self(sprintf(
            'Some of units %s have no access to bank account #%d',
            implode(', ', $unitIds),
            $bankAccountId
        ));
    }
}
