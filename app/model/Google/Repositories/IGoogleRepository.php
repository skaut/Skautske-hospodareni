<?php

declare(strict_types=1);

namespace Model\Mail\Repositories;

use Google_Client;
use Model\Common\UnitId;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\OAuth;
use Model\Google\OAuthId;

interface IGoogleRepository
{
    public function save(OAuth $oAuth) : void;

    /** @throws OAuthNotFound */
    public function find(OAuthId $oauthId) : OAuth;

    /**
     * @param int[] $unitIds
     *
     * @return array<int, OAuth[]> unitId => OAuth[]
     */
    public function findByUnits(array $unitIds) : array;

    /** @return OAuth[] */
    public function findByUnit(UnitId $unitId) : array;

    public function findByUnitAndEmail(UnitId $unitId, string $email) : OAuth;

    public function remove(OAuth $oAuth) : void;

    public function getClient() : Google_Client;
}
