<?php

declare(strict_types=1);

namespace Model\Mail\Repositories;

use Model\Common\UnitId;
use Model\Google\OAuth;
use Model\Google\OAuthId;

interface IGoogleRepository
{
    public function getAuthUrl() : string;

    public function saveAuthCode(string $code, UnitId $unitId) : void;

    public function find(OAuthId $oauthId) : ?OAuth;

    /** @return OAuth[] */
    public function findByUnitId(UnitId $unitId) : array;

    public function remove(OAuth $oAuth) : void;
}
