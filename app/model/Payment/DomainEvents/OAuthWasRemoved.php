<?php

declare(strict_types=1);

namespace Model\Payment\DomainEvents;

use Model\Google\OAuthId;

final class OAuthWasRemoved
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
