<?php

declare(strict_types=1);

namespace App\Model\Payment\Services;

use App\Model\Google\Exception\OAuthNotFound;
use App\Model\Google\OAuthId;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\IUnitResolver;

final class OAuthsAccessChecker implements IOAuthAccessChecker
{
    public function __construct(private IGoogleRepository $googleRepository, private IUnitResolver $unitResolver)
    {
    }

    /**
     * @param int[] $unitIds
     *
     * @throws OAuthNotFound
     */
    public function allUnitsHaveAccessToOAuth(array $unitIds, OAuthId $oAuthId): bool
    {
        $ownerUnitId = $this->googleRepository->find($oAuthId)->getUnitId();

        foreach ($unitIds as $unitId) {
            if ($unitId === $ownerUnitId->toInt() || $this->unitResolver->getOfficialUnitId($unitId) === $ownerUnitId->toInt()) {
                return true;
            }
        }

        return false;
    }
}
