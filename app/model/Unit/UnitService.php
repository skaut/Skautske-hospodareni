<?php

/**
 * @author Hána František
 */
class UnitService extends BaseService {
    
    protected $oficialUnits = array("stredisko", "kraj", "okres", "ustredi", "zvlastniJednotka");

    /**
     * vrací detail jednotky
     * @param int $unitId
     * @return stdClass 
     */
    public function getDetail($unitId = NULL) {
        if ($unitId === NULL)
            $unitId = $this->skautIS->getUnitId();
        try {
            return $this->skautIS->org->UnitDetail(array("ID" => $unitId));
        } catch (SkautIS_Exception $exc) {
            throw new BadRequestException("Nemáte oprávnění pro získání informací o jednotce.");
        }
    }

    /**
     * vrací nadřízenou jednotku
     * @param ID_Unit $id 
     * @return stdClass
     */
    public function getParrent($ID_Unit) {
        $ret = $this->skautIS->org->UnitAll(array("ID_UnitChild" => $ID_Unit));
        if(is_array($ret))
            return $ret[0];
        return $ret;
    }
    
    /**
     * nalezne podřízené jednotky
     * @param type $ID_Unit
     * @return array(stdClass) 
     */
    public function getChild($ID_Unit) {
        return $this->skautIS->org->UnitAll(array("ID_UnitParent" => $ID_Unit));
    }
    
    /**
     * vrací jednotku, která má právní subjektivitu
     * @param int $unitId
     * @return stdClass
     */
    public function getOficialUnit($unitId = NULL){
        $unit = $this->getDetail($unitId);
        if(!in_array($unit->ID_UnitType, $this->oficialUnits)){
            $parent = $unit->ID_UnitParent;
            $unit = $this->getOficialUnit($parent);
        }
        return $unit;
    }
    
    /**
     * vrací oficiální název na paragony
     * @param int $unitId
     * @return string
     */
    public function getOficialName($unitId){
        $unit = $this->getOficialUnit($unitId);
        return "IČO ". $unit->IC . " Junák - svaz skautů a skautek ČR, " . $unit->DisplayName.", " . $unit->Street .", " . $unit->City . ", ". $unit->Postcode;
    }

}