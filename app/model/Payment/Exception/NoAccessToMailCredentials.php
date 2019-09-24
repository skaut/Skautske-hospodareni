<?php

declare(strict_types=1);

namespace Model\Payment\Exception;

use Exception;
use function implode;
use function sprintf;

final class NoAccessToMailCredentials extends Exception
{
    /**
     * @param int[] $unitIds
     */
    public static function forUnits(array $unitIds, int $mailCredentialsId) : self
    {
        return new self(sprintf(
            'Some of units %s have no access to mail credentials #%d',
            implode(', ', $unitIds),
            $mailCredentialsId
        ));
    }
}
