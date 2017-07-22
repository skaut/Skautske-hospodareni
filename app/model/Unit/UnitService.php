<?php

namespace Model;

use Model\Unit\Repositories\IUnitRepository;
use Nette\Security\User;
use Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class UnitService
{

    protected $oficialUnits = ["stredisko", "kraj", "okres", "ustredi", "zvlastniJednotka"];

    /** @var Skautis\Skautis */
    private $skautis;

    /** @var IUnitRepository */
    private $units;


    public function __construct(Skautis\Skautis $skautis, IUnitRepository $units)
    {
        $this->skautis = $skautis;
        $this->units = $units;
    }


    public function getUnitId(): int
    {
        return (int) $this->skautis->getUser()->getUnitId();
    }

    /**
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


    /**
     * nalezne podřízené jednotky
     * @return \stdClass[]
     */
    public function getChild(int $parentId)
    {
        return $this->units->findByParent($parentId);
    }

    /**
     * vrací jednotku, která má právní subjektivitu
     * @param int $unitId
     * @return \stdClass
     */
    public function getOficialUnit($unitId = NULL)
    {
        $unit = $this->getDetail($unitId);
        if (!in_array($unit->ID_UnitType, $this->oficialUnits)) {
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

    public function getAllUnder($ID_Unit, $self = TRUE)
    {
        $data = $self ? [$ID_Unit => $this->getDetail($ID_Unit)] : [];
        foreach ($this->getChild($ID_Unit) as $u) {
            $data[$u->ID] = $u;
            $data = $data + $this->getAllUnder($u->ID, FALSE);
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

    private function getUnits(User $user, string $accessType)
    {
        $res = [];
        foreach ($user->getIdentity()->access[$accessType] as $uId => $u) {
            $res[$uId] = $u->DisplayName;
        }
        return $res;
    }

}
