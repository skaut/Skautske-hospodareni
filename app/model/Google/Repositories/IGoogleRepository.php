<?php

declare(strict_types=1);

namespace Model\Mail\Repositories;

use Google_Service_Gmail;
use Model\Common\UnitId;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\OAuth;
use Model\Google\OAuthId;

interface IGoogleRepository
{
    public function getAuthUrl() : string;

    public function saveAuthCode(string $code, UnitId $unitId) : void;

    /** @throws OAuthNotFound */
    public function find(OAuthId $oauthId) : ?OAuth;

    /**
     * @param int[] $unitIds
     *
     * @return array<int, OAuth[]> unitId => OAuth[]
     */
    public function findByUnits(array $unitIds) : array;

    /** @return OAuth[] */
    public function findByUnit(UnitId $unitId) : array;

    public function remove(OAuth $oAuth) : void;

    public function getGmailService(OAuth $oAuth) : Google_Service_Gmail;
}
