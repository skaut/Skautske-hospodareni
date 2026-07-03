<?php

declare(strict_types=1);

namespace App\Model\Mail\Repositories;

use App\Model\Common\UnitId;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\OAuthId;

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
