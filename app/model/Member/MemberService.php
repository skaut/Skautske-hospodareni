<?php

namespace Model;

use Skautis\Wsdl\WebServiceInterface;

class MemberService
{

    /** @var WebServiceInterface @todo Create anticorruption layer */
    private $organizationWebservice;

    public function __construct(WebServiceInterface $organizationWebservice)
    {
        $this->organizationWebservice = $organizationWebservice;
    }


    /**
     * vrací seznam všech osob
     * @return array
     */
    public function getAll(int $unitId, bool $onlyDirectMember, array $participants)
    {
        $all = $this->organizationWebservice->PersonAll(["ID_Unit" => $unitId, "OnlyDirectMember" => $onlyDirectMember]);
        $ret = [];

        if (empty($participants)) {
            foreach ($all as $people) {
                $ret[$people->ID] = $people->DisplayName;
            }
        } else { //odstranení jiz oznacených
            $check = [];
            foreach ($participants as $p) {
                $check[$p->ID_Person] = TRUE;
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
     * vytvoří pole jmen s ID pro combobox
     * @param bool $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     * @return array
     */
    public function getCombobox($OnlyDirectMember = FALSE, $ageLimit = NULL)
    {
        return $this->getPairs($this->organizationWebservice->PersonAll(["OnlyDirectMember" => $OnlyDirectMember]), $ageLimit);
    }

    /**
     * vrací pole osob ID => jméno
     * @param array $data - vráceno z PersonAll
     * @return array
     */
    private function getPairs($data, $ageLimit = NULL)
    {
        $res = [];
        $now = new \DateTime();
        foreach ($data as $p) {
            if ($ageLimit != NULL) {
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
