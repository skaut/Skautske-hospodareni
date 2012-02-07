<?php

/**
 * ARCHIV funkcí
 * @author sinacek
 */
class SkautisService {
    
//    function importOrgFunctions() {
//        $ret = $this->organization->__getFunctions();
//        foreach ($ret as $key => $value){
//            preg_match("/(\w+) (\w+)\((\w+) .*\)/", $value, $result);
//            dump($result[2]);
//            dibi::query( "INSERT INTO [skautis_functions]", array("type"=>1, "name"=>$result[2]) );
//        }
//        //dump($ret);
//        die();
//    }

//    /**
//     * vrací seznam rolí, které přihlášený uživatel může mít
//     * @return array(ID => DisplayName)
//     */
//    function getMyRoles() {
//        $arr = array();
//        $myDetail = $this->getMyDetail();
//        $arr['ID_User'] = isset($myDetail) ? $myDetail->ID : -1;
//        $response = $this->user->UserRoleAll($arr);
//        $ret = array();
//        foreach ($response as $role) {
//            $ret[$role->ID] = $role->DisplayName;
//        }
//        return $ret;
//    }
// 
//    /**
//     * změní roli přihlášeného uživatele
//     * @param <type> $roleId
//     */
//    function changeRole($roleId) {
//        $newUnitId = $this->user->LoginUpdate(array("ID" => $this->getToken(), "ID_UserRole" => $roleId));
//        return $this->setUnitId($newUnitId->ID_Unit);
//    }
//
//    /**
//     * 
//     * ORGANIZATION
//     * 
//     */
//    function getMyUnit() {
//        return $this->getUnitDetail();
//    }
//

//    /**
//     * vratí nadrařenou jednotku
//     * @param <type> $unitId
//     * @return unit
//     */
//    function getUnitParrent($unitId = NULL) {
//        $unitId = ($unitId === NULL) ? $this->getUnitId() : $unitId;
//        return $this->organization->UnitAll(array("ID_UnitChild" => $unitId));
//    }
//
//    /**
//     * vrací seznam podřízených jednotek
//     * @param <type> $unitId
//     * @return <type>
//     */
//    function getUnitsUnder($unitId = NULL) {
//        $unitId = $unitId ? $unitId : $this->getUnitId();
//        return $this->organization->UnitTreeAll(array("ID_UnitParent" => $unitId));
//    }

}

