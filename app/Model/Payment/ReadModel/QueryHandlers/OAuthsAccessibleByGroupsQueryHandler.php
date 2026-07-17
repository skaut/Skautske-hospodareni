<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\DTO\Google\OAuth as OAuthDTO;
use App\Model\DTO\Google\OAuthFactory;
use App\Model\Google\Entity\GoogleOAuth;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\IUnitResolver;
use App\Model\Payment\ReadModel\Queries\OAuthsAccessibleByGroupsQuery;
use App\Model\Payment\Services\IOAuthAccessChecker;
use LogicException;

use function array_map;
use function array_merge;
use function array_unique;

final class OAuthsAccessibleByGroupsQueryHandler
{
    public function __construct(
        private IOAuthAccessChecker $accessChecker,
        private IUnitResolver $unitResolver,
        private IGoogleRepository $googleRepository,
    ) {
    }

    /** @return OAuthDTO[] */
    public function __invoke(OAuthsAccessibleByGroupsQuery $query): array
    {
        $unitIds = $query->getUnitIds();
        $allOAuths = array_merge([], ...$this->googleRepository->findByUnits($this->unitsOwningOAuth($unitIds)));

        $accessibleOAuths = [];

        foreach ($allOAuths as $oAuth) {
            if (! $oAuth instanceof GoogleOAuth) {
                throw new LogicException('Assertion failed.');
            }
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
    private function unitsOwningOAuth(array $unitIds): array
    {
        return array_unique(
            array_merge([], $unitIds, array_map([$this->unitResolver, 'getOfficialUnitId'], $unitIds)),
        );
    }
}
