<?php

declare(strict_types=1);

namespace App\Model\Payment\DomainEvents;

use App\Model\Google\OAuthId;

final class OAuthWasRemoved
{
    public function __construct(private OAuthId $oAuthId)
    {
    }

    public function getOAuthId(): OAuthId
    {
        return $this->oAuthId;
    }
}
