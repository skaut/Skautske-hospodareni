<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class UnitService extends BaseService {

    protected $oficialUnits = array("stredisko", "kraj", "okres", "ustredi", "zvlastniJednotka");

    public function getUnitId() {
        return $this->skautis->getUnitId();
    }

    /**
     * vrací detail jednotky
     * @param int $unitId
     * @return stdClass 
     */
    public function getDetail($unitId = NULL) {
        if ($unitId === NULL) {
            $unitId = $this->getUnitId();
        }
        try {
            $cacheId = __FUNCTION__ . $unitId;
            if (!($res = $this->loadSes($cacheId))) {
                $res = $this->saveSes($cacheId, $this->skautis->org->UnitDetail(array("ID" => $unitId)));
            }
            return $res;
        } catch (SkautIS_Exception $exc) {
            throw new \Nette\Application\BadRequestException("Nemáte oprávnění pro získání informací o jednotce.");
        }
    }

    /**
     * vrací nadřízenou jednotku
     * @param ID_Unit $ID_Unit 
     * @return stdClass
     */
    public function getParrent($ID_Unit) {
        $ret = $this->skautis->org->UnitAll(array("ID_UnitChild" => $ID_Unit));
        if (is_array($ret)) {
            return $ret[0];
        }
        return $ret;
    }

    /**
     * nalezne podřízené jednotky
     * @param type $ID_Unit
     * @return array(stdClass) 
     */
    public function getChild($ID_Unit) {
        return $this->skautis->org->UnitAll(array("ID_UnitParent" => $ID_Unit));
    }

    /**
     * vrací jednotku, která má právní subjektivitu
     * @param int $unitId
     * @return stdClass
     */
    public function getOficialUnit($unitId = NULL) {
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
    public function getOficialName($unitId) {
        $unit = $this->getOficialUnit($unitId);
        return "IČO " . $unit->IC . " Junák - svaz skautů a skautek ČR, " . $unit->DisplayName . ", " . $unit->Street . ", " . $unit->City . ", " . $unit->Postcode;
    }

    public function getAllUnder($ID_Unit, $self = TRUE) {
        $data = $self ? array($ID_Unit => $this->getDetail($ID_Unit)) : array();
        foreach ($this->getChild($ID_Unit) as $u) {
            $data[$u->ID] = $u;
            $data = $data + $this->{__FUNCTION__}($u->ID, FALSE);
        }
        return $data;
    }

    public function getReadUnits(\Nette\Security\User $user) {
        $res = array();
        foreach ($user->getIdentity()->access['read'] as $uId => $u) {
            $res[$uId] = $u->DisplayName;
        }
        return $res;
    }

}
