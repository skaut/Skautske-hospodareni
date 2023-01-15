<?php

declare(strict_types=1);

namespace Model;

use Model\Common\Services\QueryBus;
use Model\Payment\IUnitResolver;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Model\Unit\UserHasNoUnit;
use Nette\Application\BadRequestException;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Skautis;

use function assert;

class UnitService
{
    public function __construct(private Skautis\Skautis $skautis, private IUnitRepository $units, private IUnitResolver $unitResolver, private QueryBus $queryBus)
    {
    }

    /** @throws UserHasNoUnit */
    public function getUnitId(): int
    {
        $user   = $this->skautis->getUser();
        $unitId = $user->getUnitId();

        if ($unitId === null || $unitId === 0) {
            throw UserHasNoUnit::fromLoginId($user->getLoginId());
        }

        return $unitId;
    }

    public function getOfficialUnitId(int $unitId): int
    {
        return $this->unitResolver->getOfficialUnitId($unitId);
    }

    /** @return string[] */
    public function getSubunitPairs(int $parentId, bool $useDisplayName = false): array
    {
        $subUnits = $this->units->findByParent($parentId);

        $pairs = [];
        foreach ($subUnits as $subUnit) {
            $pairs[$subUnit->getId()] = $useDisplayName ? $subUnit->getDisplayName() : $subUnit->getSortName();
        }

        return $pairs;
    }

    /**
     * vrací jednotku, která má právní subjektivitu
     */
    public function getOfficialUnit(int|null $unitId = null): Unit
    {
        $unitId       ??= $this->getUnitId();
        $officialUnitId = $this->unitResolver->getOfficialUnitId($unitId);

        return $this->units->find($officialUnitId);
    }

    /**
     * @return Unit[]|array<int, Unit>
     *
     * @throws BadRequestException
     */
    public function getAllUnder(int $unitId): array
    {
        $data = [$unitId => $this->queryBus->handle(new UnitQuery($unitId))];
        foreach ($this->units->findByParent($unitId) as $u) {
            $data[$u->getId()] = $u;
            $data             += $this->getAllUnder($u->getId());
        }

        return $data;
    }

    public function getTreeUnder(Unit $unit): Unit
    {
        $children = [];
        foreach ($this->units->findByParent($unit->getId()) as $ch) {
            $children[] = $this->getTreeUnder($ch);
        }

        return $unit->withChildren($children);
    }

    /**
     * vrací seznam jednotek, ke kterým má uživatel právo na čtení
     *
     * @return array<int, string> unit ID => unit name
     */
    public function getReadUnits(User $user): array
    {
        return $this->getUnits($user, UserService::ACCESS_READ);
    }

    /** @return string[] */
    public function getUnits(User $user, string $accessType): array
    {
        $identity = $user->getIdentity();

        assert($identity instanceof SimpleIdentity);

        $res = [];
        foreach ($identity->access[$accessType] as $uId => $u) {
            assert($u instanceof Unit);
            $res[$uId] = $u->getDisplayName();
        }

        return $res;
    }
}
