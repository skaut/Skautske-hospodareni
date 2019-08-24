<?php

declare(strict_types=1);

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use Model\Payment\IUnitResolver;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Model\Unit\UserHasNoUnit;
use Nette\Application\BadRequestException;
use Nette\Security\Identity;
use Nette\Security\User;
use Skautis;
use function assert;

class UnitService
{
    /** @var Skautis\Skautis */
    private $skautis;

    /** @var IUnitRepository */
    private $units;

    /** @var IUnitResolver */
    private $unitResolver;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(Skautis\Skautis $skautis, IUnitRepository $units, IUnitResolver $unitResolver, QueryBus $queryBus)
    {
        $this->skautis      = $skautis;
        $this->units        = $units;
        $this->unitResolver = $unitResolver;
        $this->queryBus     = $queryBus;
    }

    /**
     * @throws UserHasNoUnit
     */
    public function getUnitId() : int
    {
        $user   = $this->skautis->getUser();
        $unitId = $user->getUnitId();

        if ($unitId === null || $unitId === 0) {
            throw UserHasNoUnit::fromLoginId($user->getLoginId());
        }

        return $unitId;
    }

    public function getOfficialUnitId(int $unitId) : int
    {
        return $this->unitResolver->getOfficialUnitId($unitId);
    }

    /**
     * nalezne podřízené jednotky
     *
     * @return Unit[]
     */
    public function getSubunits(int $parentId) : array
    {
        return $this->units->findByParent($parentId);
    }

    /**
     * @return string[]
     */
    public function getSubunitPairs(int $parentId, bool $useDisplayName = false) : array
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
    public function getOfficialUnit(?int $unitId = null) : Unit
    {
        $unitId         = $unitId ?? $this->getUnitId();
        $officialUnitId = $this->unitResolver->getOfficialUnitId($unitId);

        return $this->units->find($officialUnitId);
    }

    /**
     * @return Unit[]|array<int, Unit>
     *
     * @throws BadRequestException
     */
    public function getAllUnder(int $ID_Unit) : array
    {
        $data = [$ID_Unit => $this->queryBus->handle(new UnitQuery($ID_Unit))];
        foreach ($this->units->findByParent($ID_Unit) as $u) {
            $data[$u->getId()] = $u;
            $data             += $this->getAllUnder($u->getId());
        }

        return $data;
    }

    public function getTreeUnder(Unit $unit) : Unit
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
    public function getReadUnits(User $user) : array
    {
        return $this->getUnits($user, BaseService::ACCESS_READ);
    }

    /**
     * @return string[]
     */
    public function getUnits(User $user, string $accessType) : array
    {
        $identity = $user->getIdentity();

        assert($identity instanceof Identity);

        $res = [];
        foreach ($identity->access[$accessType] as $uId => $u) {
            assert($u instanceof Unit);
            $res[$uId] = $u->getDisplayName();
        }

        return $res;
    }
}
