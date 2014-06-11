<?php

namespace Model;

/**
 * @author sinacek
 */
class MemberService extends BaseService {

    /**
     * vrací seznam všech osob
     * @param ID_Unit $unitId - ID_Unit
     * @param bool $onlyDirectMember - pouze přímé členy?
     * @return array 
     */
    public function getAll($unitId = NULL, $onlyDirectMember = true, $participants = NULL) {
        $unitId = $unitId === NULL ? $this->skautIS->getUnitId() : $unitId;
        $onlyDirectMember = (bool) $onlyDirectMember;

        $all = $this->skautIS->org->PersonAll(array("ID_Unit" => $unitId, "OnlyDirectMember" => $onlyDirectMember));
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
        return array_values($this->getPairs($this->skautIS->org->PersonAll(array("OnlyDirectMember" => $OnlyDirectMember)), $adultOnly));
    }

    /**
     * vytvoří pole jmen s ID pro combobox
     * @param bool $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     * @return array
     */
    public function getCombobox($OnlyDirectMember = false, $adultOnly = false) {
        return $this->getPairs($this->skautIS->org->PersonAll(array("OnlyDirectMember" => $OnlyDirectMember)), $adultOnly);
    }

    /**
     * vrací pole osob ID => jméno
     * @param array $data - vráceno z PersonAll
     * @return array 
     */
    private function getPairs($data, $adultOnly = false) {
        $res = array();
        $now = new \DateTime();
        foreach ($data as $p) {
            $birth = new \DateTime($p->Birthday);
            $interval = $now->diff($birth);
            $diff = $interval->format("%y");
            if ($adultOnly && $diff < 18) {
                continue;
            }
            $res[$p->ID] = $p->LastName . " " . $p->FirstName . ($adultOnly ? " (" . $diff . ")" : "");
        }
        asort($res);
        return $res;
    }

}
