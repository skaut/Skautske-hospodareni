<?php

declare(strict_types=1);

namespace Model\Google\ReadModel\Queries;

use Model\Google\OAuthId;

/**
 * @see OAuthQueryHandler
 */
final class OAuthQuery
{
    private OAuthId $oAuthId;

    public function __construct(OAuthId $oAuthId)
    {
        $this->oAuthId = $oAuthId;
    }

    public function getOAuthId() : OAuthId
    {
        return $this->oAuthId;
    }
}
