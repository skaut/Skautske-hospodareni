<?php

/**
 * @author Hána František
 */
class UserService extends BaseService {

    /**
     * varcí ID role aktuálně přihlášeného uživatele
     * @return type 
     */
    public function getRoleId() {
        return $this->skautIS->getRoleId();
    }

    /**
     * vrací pole 
     * @return array všech dostupných rolí přihlášeného uživatele
     */
    public function getAllSkautISRoles() {
        return $this->skautIS->user->UserRoleAll(array("ID_User" => $this->getUserDetail()->ID));
    }

    public function getUserDetail() {
        $id = __FUNCTION__ ;
        if (!($res = $this->load($id))) {
            $res = $this->save($id, $this->skautIS->user->userDetail());
        }
        return $res;
    }

    /**
     * změní přihlášenou roli do skautISu
     * @param ID_Role $id
     */
    public function updateSkautISRole($id) {
        $unitId = $this->skautIS->user->LoginUpdate(array("ID_UserRole" => $id, "ID" => $this->skautIS->getToken()));
        if ($unitId) {
            $this->skautIS->setRoleId($id);
            $this->skautIS->setUnitId($unitId->ID_Unit);
        }
    }

    public function getUserData() {
        $detail = $this->getUserDetail();
        $person = $this->skautIS->org->personDetail((array("ID" => $detail->ID_Person)));
        $detail->DisplayName = $person->DisplayName;
        return $detail;
    }

    /**
     * kontroluje jestli je přihlášení platné
     * @return type 
     */
    public function isLoggedIn() {
        return $this->skautIS->isLoggedIn();
    }

    /**
     * vytvoří pole jmen pro automatické doplňování
     * @param bool $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     * @return array
     */
    public function getAC($OnlyDirectMember = false) {
        return $this->getPairs($this->skautIS->org->PersonAll(array("OnlyDirectMember" => $OnlyDirectMember)));
    }

    /**
     * vytvoří pole jmen s ID pro combobox
     * @param bool $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     * @return array
     */
    public function getCombobox($OnlyDirectMember = false) {
        return $this->getPairs($this->skautIS->org->PersonAll(array("OnlyDirectMember" => $OnlyDirectMember)));
    }
    
    /**
     * vrací pole osob ID => jméno
     * @param array $data - vráceno z PersonAll
     * @return array 
     */
    private function getPairs($data){
        $res = array();
        foreach ($data as $p) {
            $res[$p->ID] = $p->LastName . " " . $p->FirstName;
        }
        return $res;
    }

}