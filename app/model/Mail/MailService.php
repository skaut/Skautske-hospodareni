<?php

declare(strict_types=1);

namespace Model;

use Model\DTO\Google\OAuth as OAuthDTO;
use Model\DTO\Google\OAuthFactory;
use Model\Google\OAuth;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\IUnitResolver;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;

class MailService
{
    /** @var IGoogleRepository */
    private $googleRepository;

    /** @var IUnitResolver */
    private $unitResolver;

    public function __construct(IGoogleRepository $credentials, IUnitResolver $unitResolver)
    {
        $this->googleRepository = $credentials;
        $this->unitResolver     = $unitResolver;
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<int|string, OAuthDTO>
     */
    public function getAll(array $unitIds) : array
    {
        $mails = $this->findForUnits($unitIds);

        return array_map([OAuthFactory::class, 'create'], array_merge([], ...$mails));
    }

    /**
     * @param int[] $unitIds
     *
     * @return int[]
     */
    private function getAccessibleUnitIds(array $unitIds) : array
    {
        $res = $unitIds;
        foreach ($unitIds as $uid) {
            $res[] = $this->unitResolver->getOfficialUnitId($uid);
        }

        return array_unique($unitIds);
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<int, OAuth[]> unitId => OAuth[]
     */
    private function findForUnits(array $unitIds) : array
    {
        $unitIds = $this->getAccessibleUnitIds($unitIds);

        return array_filter($this->googleRepository->findByUnits($unitIds));
    }
}
