<?php

declare(strict_types=1);

namespace Model\Google\Commands;

use Model\Google\OAuthId;

/**
 * @see RemoveOAuthHandler
 */
final class RemoveOAuth
{
    /** @var OAuthId */
    private $oAuthId;

    public function __construct(OAuthId $oAuthId)
    {
        $this->oAuthId = $oAuthId;
    }

    public function getOAuthId() : OAuthId
    {
        return $this->oAuthId;
    }
}
