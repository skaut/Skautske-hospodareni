<?php

declare(strict_types=1);

namespace Model\Google\Commands;

use Model\Google\OAuthId;

/** @see RemoveOAuthHandler */
final class RemoveOAuth
{
    public function __construct(private OAuthId $oAuthId)
    {
    }

    public function getOAuthId(): OAuthId
    {
        return $this->oAuthId;
    }
}
