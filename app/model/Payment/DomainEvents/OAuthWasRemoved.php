<?php

declare(strict_types=1);

namespace Model\Payment\DomainEvents;

use Model\Google\OAuthId;

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
