<?php

declare(strict_types=1);

namespace App\Model\Google\ReadModel\Queries;

use App\Model\Google\OAuthId;

/** @see OAuthQueryHandler */
final class OAuthQuery
{
    public function __construct(private OAuthId $oAuthId)
    {
    }

    public function getOAuthId(): OAuthId
    {
        return $this->oAuthId;
    }
}
