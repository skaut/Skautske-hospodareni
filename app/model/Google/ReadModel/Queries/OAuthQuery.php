<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\Queries;

use Model\Google\OAuthId;

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
