<?php

namespace Model;

use Model\Payment\IUnitResolver;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Nette\Security\User;
use Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class UnitService
{

    public const OFFICIAL_UNIT_TYPES = ["stredisko", "kraj", "okres", "ustredi", "zvlastniJednotka"];

    /** @var Skautis\Skautis */
    private $skautis;

    /** @var IUnitRepository */
    private $units;

    /** @var IUnitResolver */
    private $unitResolver;


    public function __construct(Skautis\Skautis $skautis, IUnitRepository $units, IUnitResolver $unitResolver)
    {
        $this->skautis = $skautis;
        $this->units = $units;
        $this->unitResolver = $unitResolver;
    }


    public function getUnitId(): int
    {
        return (int)$this->skautis->getUser()->getUnitId();
    }

    /**
     * @deprecated Use UnitService::getDetailV2()
     *
     * vrací detail jednotky
     * @param int|NULL $unitId
     * @return \stdClass
     * @throws \Nette\Application\BadRequestException
     */
    public function getDetail($unitId = NULL)
    {
        if ($unitId === NULL) {
            $unitId = $this->getUnitId();
        }

        try {
            return $this->units->find($unitId);
        } catch (Skautis\Exception $exc) {
            throw new \Nette\Application\BadRequestException("Nemáte oprávnění pro získání informací o jednotce.");
        }
    }

    public function getOfficialUnitId(int $unitId): int
    {
        return $this->unitResolver->getOfficialUnitId($unitId);
    }

    /**
     * @throws \Nette\Application\BadRequestException
     */
    public function getDetailV2(int $unitId): Unit
    {
        try {
            return $this->units->find($unitId, TRUE);
        } catch (Skautis\Exception $exc) {
            throw new \Nette\Application\BadRequestException("Nemáte oprávnění pro získání informací o jednotce.");
        }
    }

    /**
     * nalezne podřízené jednotky
     * @return Unit[]
     */
    public function getChild(int $parentId)
    {
        return $this->units->findByParent($parentId);
    }

    /**
     * @return string[]
     */
    public function getSubunitPairs(int $parentId): array
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
     * @param int $unitId
     * @return \stdClass
     */
    public function getOficialUnit($unitId = NULL)
    {
        $unit = $this->getDetail($unitId);
        if (!in_array($unit->ID_UnitType, self::OFFICIAL_UNIT_TYPES)) {
            $parent = $unit->ID_UnitParent;
            $unit = $this->getOficialUnit($parent);
        }
        return $unit;
    }

    /**
     * vrací oficiální název organizační jednotky (využití na paragonech)
     * @param int $unitId
     * @return string
     */
    public function getOficialName($unitId)
    {
        $unit = $this->getOficialUnit($unitId);
        return "IČO " . $unit->IC . " " . $unit->FullDisplayName . ", " . $unit->Street . ", " . $unit->City . ", " . $unit->Postcode;
    }

    public function getAllUnder(int $ID_Unit, $self = TRUE)
    {
        $data = $self ? [$ID_Unit => $this->getDetail($ID_Unit)] : [];
        foreach ($this->units->findByParent($ID_Unit) as $u) {
            $data[$u->getId()] = $u;
            $data = $data + $this->getAllUnder($u->getId(), FALSE);
        }
        return $data;
    }


    /**
     * vrací seznam jednotek, ke kterým má uživatel právo na čtení
     * @param User $user
     * @return array
     */
    public function getReadUnits(User $user): array
    {
        return $this->getUnits($user, BaseService::ACCESS_READ);
    }

    /**
     * vrací seznam jednotek, ke kterým má uživatel právo na zápis a editaci
     * @param User $user
     * @return array
     */
    public function getEditUnits(User $user): array
    {
        return $this->getUnits($user, BaseService::ACCESS_EDIT);
    }


    public function getUnits(User $user, string $accessType)
    {
        /* @var $identity \Nette\Security\Identity */
        $identity = $user->getIdentity();

        $res = [];
        foreach ($identity->access[$accessType] as $uId => $u) {
            $res[$uId] = $u instanceof Unit ? $u->getDisplayName() : $u->DisplayName;
        }
        return $res;
    }

    /**
     * load camp troops
     * @param \stdClass $camp
     * @return array
     */
    public function getCampTroops(\stdClass $camp)
    {
        if (!isset($camp->ID_UnitArray->string)) {
            return [];
        }

        $troopIds = $camp->ID_UnitArray->string;
        $troopIds = is_array($troopIds) ? $troopIds : [$troopIds];

        return array_map(function ($id) {
            return $this->getDetail($id);
        }, $troopIds);
    }

}
