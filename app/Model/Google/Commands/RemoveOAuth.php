<?php

declare(strict_types=1);

namespace App\Model\Google\Commands;

use App\Model\Google\OAuthId;

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
