<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class MemberService extends BaseService {

    /**
     * vrací seznam všech osob
     * @param ID_Unit $unitId - ID_Unit
     * @param bool $onlyDirectMember - pouze přímé členy?
     * @return array 
     */
    public function getAll($unitId = NULL, $onlyDirectMember = true, $participants = NULL) {
        $unitId = $unitId === NULL ? $this->skautis->getUser()->getUnitId() : $unitId;
        
        $all = $this->skautis->org->PersonAll(array("ID_Unit" => $unitId, "OnlyDirectMember" => (bool) $onlyDirectMember));
        $ret = array();

        if (empty($participants)) {
            foreach ($all as $people) {
                $ret[$people->ID] = $people->DisplayName;
            }
        } else { //odstranení jiz oznacených
            $check = array();
            foreach ($participants as $p) {
                $check[$p->ID_Person] = true;
            }
            foreach ($all as $p) {
                if (!array_key_exists($p->ID, $check)) {
                    $ret[$p->ID] = $p->DisplayName;
                }
            }
        }
        natcasesort($ret);
        return $ret;
    }

    /**
     * vytvoří pole jmen pro automatické doplňování
     * @param bool $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     * @return array
     */
    public function getAC($OnlyDirectMember = false, $adultOnly = false) {
        return array_values($this->getPairs($this->skautis->org->PersonAll(array("OnlyDirectMember" => $OnlyDirectMember)), $adultOnly));
    }

    /**
     * vytvoří pole jmen s ID pro combobox
     * @param bool $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     * @return array
     */
    public function getCombobox($OnlyDirectMember = FALSE, $ageLimit = NULL) {
        return $this->getPairs($this->skautis->org->PersonAll(array("OnlyDirectMember" => $OnlyDirectMember)), $ageLimit);
    }

    /**
     * vrací pole osob ID => jméno
     * @param array $data - vráceno z PersonAll
     * @return array 
     */
    private function getPairs($data, $ageLimit = NULL) {
        $res = array();
        $now = new \DateTime();
        foreach ($data as $p) {
            if($ageLimit !=NULL){
                $birth = new \DateTime($p->Birthday);
                $interval = $now->diff($birth);
                $diff = $interval->format("%y");
                if ($diff < $ageLimit) {
                    continue;
                }
            }
            $res[$p->ID] = $p->DisplayName;
        }
        asort($res);
        return $res;
    }

}
