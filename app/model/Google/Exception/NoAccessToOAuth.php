<?php

declare(strict_types=1);

namespace Model\Google\Exception;

use Exception;
use Model\Google\OAuthId;
use function implode;
use function sprintf;

final class NoAccessToOAuth extends Exception
{
    /**
     * @param int[] $unitIds
     */
    public static function forUnits(array $unitIds, OAuthId $mailCredentialsId) : self
    {
        return new self(sprintf(
            'Some of units %s have no access to mail credentials #%d',
            implode(', ', $unitIds),
            $mailCredentialsId->toString()
        ));
    }
}
