<?php

declare(strict_types=1);

namespace App\Model\Google\ReadModel\Queries;

use App\Model\Google\OAuthId;
use App\Model\Google\ReadModel\QueryHandlers\BrokenSenderAccountsQueryHandler;

/** @see BrokenSenderAccountsQueryHandler */
final class BrokenSenderAccountsQuery
{
    /** @param list<OAuthId> $oAuthIds senders to check, e.g. of the payment groups the user sees */
    public function __construct(private array $oAuthIds)
    {
    }

    /** @return list<OAuthId> */
    public function getOAuthIds(): array
    {
        return $this->oAuthIds;
    }
}
