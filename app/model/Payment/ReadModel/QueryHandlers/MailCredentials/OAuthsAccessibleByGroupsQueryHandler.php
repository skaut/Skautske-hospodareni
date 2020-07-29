<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers\MailCredentials;

use Model\DTO\Google\OAuth as OAuthDTO;
use Model\DTO\Google\OAuthFactory;
use Model\Google\OAuth;
use Model\Mail\Repositories\IGoogleRepository;
use Model\Payment\IUnitResolver;
use Model\Payment\ReadModel\Queries\MailCredentials\OAuthsAccessibleByGroupsQuery;
use Model\Payment\Services\IOAuthAccessChecker;
use function array_map;
use function array_merge;
use function array_unique;
use function assert;

final class OAuthsAccessibleByGroupsQueryHandler
{
    /** @var IOAuthAccessChecker */
    private $accessChecker;

    /** @var IUnitResolver */
    private $unitResolver;

    /** @var IGoogleRepository */
    private $googleRepository;

    public function __construct(
        IOAuthAccessChecker $accessChecker,
        IUnitResolver $unitResolver,
        IGoogleRepository $googleRepository
    ) {
        $this->accessChecker    = $accessChecker;
        $this->unitResolver     = $unitResolver;
        $this->googleRepository = $googleRepository;
    }

    /**
     * @return OAuthDTO[]
     */
    public function __invoke(OAuthsAccessibleByGroupsQuery $query) : array
    {
        $unitIds   = $query->getUnitIds();
        $allOAuths = array_merge(
            [],
            ...$this->googleRepository->findByUnits($this->unitsOwningMailCredentials($unitIds))
        );

        $accessibleOAuths = [];

        foreach ($allOAuths as $oAuth) {
            assert($oAuth instanceof OAuth);

            if (! $this->accessChecker->allUnitsHaveAccessToOAuth($unitIds, $oAuth->getId())) {
                continue;
            }

            $accessibleOAuths[] = OAuthFactory::create($oAuth);
        }

        return $accessibleOAuths;
    }

    /**
     * @param int[] $unitIds
     *
     * @return int[]
     */
    private function unitsOwningMailCredentials(array $unitIds) : array
    {
        return array_unique(
            array_merge([], $unitIds, array_map([$this->unitResolver, 'getOfficialUnitId'], $unitIds))
        );
    }
}
