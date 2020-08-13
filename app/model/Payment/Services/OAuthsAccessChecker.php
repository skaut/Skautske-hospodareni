<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Model\Google\Exception\OAuthNotFound;
use Model\Google\OAuthId;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\IUnitResolver;

final class OAuthsAccessChecker implements IOAuthAccessChecker
{
    /** @var IGoogleRepository */
    private $googleRepository;

    /** @var IUnitResolver */
    private $unitResolver;

    public function __construct(IGoogleRepository $googleRepository, IUnitResolver $unitResolver)
    {
        $this->googleRepository = $googleRepository;
        $this->unitResolver     = $unitResolver;
    }

    /**
     * @param int[] $unitIds
     *
     * @throws OAuthNotFound
     */
    public function allUnitsHaveAccessToOAuth(array $unitIds, OAuthId $oAuthId) : bool
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
