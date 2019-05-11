<?php

declare(strict_types=1);

namespace Model;

use Model\Payment\IUnitResolver;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Model\Unit\UserHasNoUnit;
use Nette\Application\BadRequestException;
use Nette\Security\Identity;
use Nette\Security\User;
use Skautis;
use stdClass;
use function assert;

class UnitService
{
    /** @var Skautis\Skautis */
    private $skautis;

    /** @var IUnitRepository */
    private $units;

    /** @var IUnitResolver */
    private $unitResolver;

    public function __construct(Skautis\Skautis $skautis, IUnitRepository $units, IUnitResolver $unitResolver)
    {
        $this->skautis      = $skautis;
        $this->units        = $units;
        $this->unitResolver = $unitResolver;
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

    /**
     * @deprecated Use QueryBus with UnitQuery
     *
     * vrací detail jednotky
     *
     * @throws BadRequestException
     */
    public function getDetail(?int $unitId = null) : stdClass
    {
        if ($unitId === null) {
            $unitId = $this->getUnitId();
        }

        try {
            return $this->units->findAsStdClass($unitId);
        } catch (Skautis\Exception $exc) {
            throw new BadRequestException('Nemáte oprávnění pro získání informací o jednotce.');
        }
    }

    public function getOfficialUnitId(int $unitId) : int
    {
        return $this->unitResolver->getOfficialUnitId($unitId);
    }

    /**
     * @deprecated Use QueryBus with UnitQuery
     *
     * @throws BadRequestException
     */
    public function getDetailV2(int $unitId) : Unit
    {
        try {
            return $this->units->find($unitId);
        } catch (Skautis\Exception $exc) {
            throw new BadRequestException('Nemáte oprávnění pro získání informací o jednotce.');
        }
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
     * vrací oficiální název organizační jednotky (využití na paragonech)
     */
    public function getOfficialName(int $unitId) : string
    {
        $unit = $this->getOfficialUnit($unitId);

        return $unit->getFullDisplayNameWithAddress();
    }

    /**
     * @return Unit[]|array<int, Unit>
     *
     * @throws BadRequestException
     */
    public function getAllUnder(int $ID_Unit) : array
    {
        $data = [$ID_Unit => $this->getDetailV2($ID_Unit)];
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
            $res[$uId] = $u instanceof Unit ? $u->getDisplayName() : $u->DisplayName;
        }

        return $res;
    }
}
