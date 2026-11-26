<?php

declare(strict_types=1);

namespace App\Model\Payment\DomainEvents;

use App\Model\Google\OAuthId;

/** Google accepts the connected account again, e.g. after it was connected again. */
final class OAuthWasRestored
{
    public function __construct(private OAuthId $oAuthId)
    {
    }

    public function getOAuthId(): OAuthId
    {
        return $this->oAuthId;
    }
}
