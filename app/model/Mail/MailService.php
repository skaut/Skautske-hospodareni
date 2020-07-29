<?php

declare(strict_types=1);

namespace Model;

use Model\DTO\Google\OAuth;
use Model\DTO\Google\OAuthFactory;
use Model\DTO\Payment\Mail;
use Model\Google\OAuthNotFound;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\IUnitResolver;
use Model\Payment\MailCredentials;
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

    public function get(int $id) : ?OAuth
    {
        try {
            return OAuthFactory::create(
                $this->googleRepository->find($id)
            );
        } catch (OAuthNotFound $e) {
            return null;
        }
    }

    /**
     * @param int[] $unitIds
     *
     * @return Mail[]
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
     * @return array<int, MailCredentials[]>
     */
    private function findForUnits(array $unitIds) : array
    {
        $unitIds = $this->getAccessibleUnitIds($unitIds);

        return array_filter($this->googleRepository->findByUnits($unitIds));
    }
}
