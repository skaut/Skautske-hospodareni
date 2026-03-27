<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\DTO\Google\OAuth as OAuthDTO;
use App\Model\DTO\Google\OAuthFactory;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\IUnitResolver;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;

class MailService
{
    private IGoogleRepository $googleRepository;

    public function __construct(IGoogleRepository $credentials, private IUnitResolver $unitResolver)
    {
        $this->googleRepository = $credentials;
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<int|string, OAuthDTO>
     */
    public function getAll(array $unitIds): array
    {
        $mails = $this->findForUnits($unitIds);

        return array_map([OAuthFactory::class, 'create'], array_merge([], ...$mails));
    }

    /**
     * @param int[] $unitIds
     *
     * @return int[]
     */
    private function getAccessibleUnitIds(array $unitIds): array
    {
        $res = $unitIds;
        foreach ($unitIds as $uid) {
            $res[] = $this->unitResolver->getOfficialUnitId($uid);
        }

        return array_unique($res);
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<int, GoogleOAuth[]> unitId => OAuth[]
     */
    private function findForUnits(array $unitIds): array
    {
        $unitIds = $this->getAccessibleUnitIds($unitIds);

        return array_filter($this->googleRepository->findByUnits($unitIds));
    }
}
