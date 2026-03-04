<?php

declare(strict_types=1);

namespace Model\Mail\Repositories;

use Entity\GoogleOAuth;
use Model\Common\UnitId;
use Model\Google\Exception\OAuthNotFound;
use Model\Google\OAuthId;

interface IGoogleRepository
{
    public function save(GoogleOAuth $oAuth): void;

    /** @throws OAuthNotFound */
    public function find(OAuthId $oauthId): GoogleOAuth;

    /**
     * @param int[] $unitIds
     *
     * @return array<int, GoogleOAuth[]> unitId => OAuth[]
     */
    public function findByUnits(array $unitIds): array;

    public function findByUnitAndEmail(UnitId $unitId, string $email): GoogleOAuth;

    public function remove(GoogleOAuth $oAuth): void;
}
