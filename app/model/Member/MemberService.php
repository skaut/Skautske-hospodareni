<?php

namespace Model;

use Skautis\User;
use Skautis\Wsdl\WebServiceInterface;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class MemberService
{

    /** @var WebServiceInterface @todo Create anticorruption layer */
    private $organizationWebservice;

    /** @var User */
    private $skautisUser;


    public function __construct(WebServiceInterface $organizationWebservice, User $skautisUser)
    {
        $this->organizationWebservice = $organizationWebservice;
        $this->skautisUser = $skautisUser;
    }


    /**
     * vrací seznam všech osob
     * @param int $unitId - ID_Unit
     * @param bool $onlyDirectMember - pouze přímé členy?
     * @return array
     */
    public function getAll($unitId = NULL, $onlyDirectMember = TRUE, $participants = NULL)
    {
        if($unitId === NULL) {
            trigger_error('Use UnitService::getUnitId() to obtain current unit id', E_USER_DEPRECATED);
            $unitId = $this->skautisUser->getUnitId();
        }

        $all = $this->organizationWebservice->PersonAll(["ID_Unit" => $unitId, "OnlyDirectMember" => (bool)$onlyDirectMember]);
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
     * vytvoří pole jmen pro automatické doplňování
     * @param bool $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     * @return array
     */
    public function getAC($OnlyDirectMember = FALSE, $adultOnly = FALSE)
    {
        return array_values($this->getPairs($this->organizationWebservice->PersonAll(["OnlyDirectMember" => $OnlyDirectMember]), $adultOnly));
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
