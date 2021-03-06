<?php

declare(strict_types=1);

namespace Model\Mail\Repositories;

use Model\Common\UnitId;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\OAuth;
use Model\Google\OAuthId;

interface IGoogleRepository
{
    public function save(OAuth $oAuth): void;

    /** @throws OAuthNotFound */
    public function find(OAuthId $oauthId): OAuth;

    /**
     * @param int[] $unitIds
     *
     * @return array<int, OAuth[]> unitId => OAuth[]
     */
    public function findByUnits(array $unitIds): array;

    public function findByUnitAndEmail(UnitId $unitId, string $email): OAuth;

    public function remove(OAuth $oAuth): void;
}
