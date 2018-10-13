<?php

declare(strict_types=1);

namespace Model;

use Model\Payment\IUnitResolver;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Model\Unit\UnitNotFound;
use Model\User\ReadModel\Queries\EditableUnitsQuery;
use Nette\Application\BadRequestException;
use Nette\Security\Identity;
use Nette\Security\User;
use Skautis;
use function is_array;

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


    public function getUnitId() : int
    {
        return (int) $this->skautis->getUser()->getUnitId();
    }

    /**
     * @deprecated Use QueryBus with UnitQuery
     *
     * vrací detail jednotky
     * @throws BadRequestException
     */
    public function getDetail(?int $unitId = null) : \stdClass
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
    public function getSubunitPairs(int $parentId) : array
    {
        $subUnits = $this->units->findByParent($parentId);

        $pairs = [];
        foreach ($subUnits as $subUnit) {
            $pairs[$subUnit->getId()] = $subUnit->getSortName();
        }

        return $pairs;
    }

    /**
     * vrací jednotku, která má právní subjektivitu
     */
    public function getOfficialUnit(?int $unitId = null) : \stdClass
    {
        $unitId         = $unitId ?? $this->getUnitId();
        $officialUnitId = $this->unitResolver->getOfficialUnitId($unitId);

        return $this->getDetail($officialUnitId);
    }

    /**
     * vrací oficiální název organizační jednotky (využití na paragonech)
     */
    public function getOficialName(int $unitId) : string
    {
        $unit = $this->getOfficialUnit($unitId);
        return 'IČO ' . $unit->IC . ' ' . $unit->FullDisplayName . ', ' . $unit->Street . ', ' . $unit->City . ', ' . $unit->Postcode;
    }

    /**
     * @return Unit[]
     * @throws BadRequestException
     */
    public function getAllUnder(int $ID_Unit, $tree = false) : array
    {
        $data = [$ID_Unit => $this->getDetailV2($ID_Unit)];
        foreach ($this->units->findByParent($ID_Unit) as $u) {
            if ($tree) {
                $data[$u->getId()] = $u->withChildren($this->getAllUnder($u->getId(), $tree));
            } else {
                $data[$u->getId()] = $u;
                $data              = $data + $this->getAllUnder($u->getId());
            }
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
     * @return string[]
     */
    public function getReadUnits(User $user) : array
    {
        return $this->getUnits($user, BaseService::ACCESS_READ);
    }

    /**
     * @deprecated use EditableUnitsQuery
     * @see EditableUnitsQuery
     *
     * vrací seznam jednotek, ke kterým má uživatel právo na zápis a editaci
     * @return string[]
     */
    public function getEditUnits(User $user) : array
    {
        return $this->getUnits($user, BaseService::ACCESS_EDIT);
    }

    /**
     * @return string[]
     */
    public function getUnits(User $user, string $accessType) : array
    {
        /** @var Identity $identity */
        $identity = $user->getIdentity();

        $res = [];
        foreach ($identity->access[$accessType] as $uId => $u) {
            $res[$uId] = $u instanceof Unit ? $u->getDisplayName() : $u->DisplayName;
        }
        return $res;
    }

    /**
     * load camp troops
     *
     * @return string[]
     */
    public function getCampTroopNames(\stdClass $camp) : array
    {
        if (! isset($camp->ID_UnitArray->string)) {
            return [];
        }

        $troopIds = $camp->ID_UnitArray->string;
        $troopIds = is_array($troopIds) ? $troopIds : [$troopIds];

        $troopNames = [];

        foreach ($troopIds as $troopId) {
            try {
                $unit = $this->units->find((int) $troopId);
            } catch (UnitNotFound $e) {
                // Removed troops are returned as well https://github.com/skaut/Skautske-hospodareni/issues/483
                continue;
            }

            $troopNames[] = $unit->getDisplayName();
        }

        return $troopNames;
    }
}
