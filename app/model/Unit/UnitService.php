<?php

namespace Model;

use Nette\Security\User;
use Skautis;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class UnitService extends BaseService
{

    protected $oficialUnits = ["stredisko", "kraj", "okres", "ustredi", "zvlastniJednotka"];

    public function getUnitId()
    {
        return $this->skautis->getUser()->getUnitId();
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
            $cacheId = __FUNCTION__ . $unitId;
            if (!($res = $this->loadSes($cacheId))) {
                $res = $this->saveSes($cacheId, $this->skautis->org->UnitDetail(["ID" => $unitId]));
            }
            return $res;
        } catch (Skautis\Exception $exc) {
            throw new \Nette\Application\BadRequestException("Nemáte oprávnění pro získání informací o jednotce.");
        }
    }

    /**
     * vrací nadřízenou jednotku
     * @param int $ID_Unit
     * @return \stdClass
     */
    public function getParrent($ID_Unit)
    {
        $ret = $this->skautis->org->UnitAll(["ID_UnitChild" => $ID_Unit]);
        if (is_array($ret)) {
            return $ret[0];
        }
        return $ret;
    }

    /**
     * nalezne podřízené jednotky
     * @param int $ID_Unit
     * @return \stdClass[]
     */
    public function getChild($ID_Unit)
    {
        return $this->skautis->org->UnitAll(["ID_UnitParent" => $ID_Unit]);
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
            $data = $data + $this->{__FUNCTION__}($u->ID, FALSE);
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
        return $this->getUnits($user, self::ACCESS_READ);
    }

    /**
     * vrací seznam jednotek, ke kterým má uživatel právo na zápis a editaci
     * @param User $user
     * @return array
     */
    public function getEditUnits(User $user): array
    {
        return $this->getUnits($user, self::ACCESS_EDIT);
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
