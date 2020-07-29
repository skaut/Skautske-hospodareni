<?php

declare(strict_types=1);

namespace Model\Mail\Repositories;

use Google_Service_Gmail;
use Model\Common\UnitId;
use Model\Google\OAuth;
use Model\Google\OAuthId;
use Model\Google\OAuthNotFound;

interface IGoogleRepository
{
    public function getAuthUrl() : string;

    public function saveAuthCode(string $code, UnitId $unitId) : void;

    /** @throws OAuthNotFound */
    public function find(OAuthId $oauthId) : ?OAuth;

    /**
     * @param int[] $unitIds
     *
     * @return OAuth[]
     */
    public function findByUnits(array $unitIds) : array;

    public function remove(OAuth $oAuth) : void;

    public function getGmailService(OAuth $oAuth) : Google_Service_Gmail;
}
