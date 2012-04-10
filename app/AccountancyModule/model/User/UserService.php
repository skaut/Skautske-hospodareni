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
        return $this->skautIS->user->UserRoleAll(array("ID_User" => $this->skautIS->getMyDetail()->ID));
    }
    
    /**
     * změní přihlášenou roli do skautISu
     * @param ID_Role $id
     */
    public function updateSkautISRole($id){
        $unitId = $this->skautIS->user->LoginUpdate(array("ID_UserRole"=>$id, "ID"=>$this->skautIS->getToken()));
        if($unitId){
            $this->skautIS->setRoleId($id);
            $this->skautIS->setUnitId($unitId->ID_Unit);
        }
    }
    
    public function getUserData(){
        $data = $this->skautIS->getMyDetail();
        $person = $this->skautIS->org->personDetail((array("ID"=>$data->ID_Person)));
        $data->DisplayName = $person->DisplayName;
        return $data;
    }

        /**
     * kontroluje jestli je přihlášení platné
     * @return type 
     */
    public function isLoggedIn(){
        return $this->skautIS->isLoggedIn();
    }

    /**
     * vytvoří pole jmen pro automatické doplňování
     * @param type $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     * @return array
     */
    public function getAC($OnlyDirectMember = false) {
        $data = $this->skautIS->org->PersonAll(array("OnlyDirectMember" => $OnlyDirectMember));
        $ac = array();
        foreach ($data as $p) {
            $ac[] = $p->LastName . " " . $p->FirstName;
        }

        return $ac;
    }

    /**
     * vytvoří pole jmen s ID pro combobox
     * @param type $OnlyDirectMember - vybrat pouze z aktuální jednotky?
     * @return array
     */
    public function getCombobox($OnlyDirectMember = false) {
        if ($OnlyDirectMember == NULL)
            $OnlyDirectMember = false;
        $data = $this->skautIS->org->PersonAll(array("OnlyDirectMember" => $OnlyDirectMember));
        $ac = array();
        foreach ($data as $p) {
            $ac[$p->ID] = $p->LastName . " " . $p->FirstName;
        }
        return $ac;
    }

}