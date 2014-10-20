<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class UserService extends BaseService {

    /**
     * varcí ID role aktuálně přihlášeného uživatele
     * @return type 
     */
    public function getRoleId() {
        return $this->skautis->getRoleId();
    }

    /**
     * vrací pole 
     * @return array všech dostupných rolí přihlášeného uživatele
     */
    public function getAllSkautISRoles($activeOnly = true) {
        return $this->skautis->user->UserRoleAll(array("ID_User" => $this->getUserDetail()->ID, "IsActive" => $activeOnly));
    }

    public function getUserDetail() {
        $id = __FUNCTION__;
        if (!($res = $this->loadSes($id))) {
            $res = $this->saveSes($id, $this->skautis->user->UserDetail());
        }
        return $res;
    }

    /**
     * změní přihlášenou roli do skautISu
     * @param ID_Role $id
     */
    public function updateSkautISRole($id) {
        $unitId = $this->skautis->user->LoginUpdate(array("ID_UserRole" => $id, "ID" => $this->skautis->getToken()));
        if ($unitId) {
            $this->skautis->setRoleId($id);
            $this->skautis->setUnitId($unitId->ID_Unit);
        }
    }

    /**
     * vrací kompletní seznam informací o přihlášené osobě
     * @return type 
     */
    public function getPersonalDetail() {
        $user = $this->getUserDetail();
        $person = $this->skautis->org->personDetail((array("ID" => $user->ID_Person)));
        return $person;
    }

    /**
     * kontroluje jestli je přihlášení platné
     * @return type 
     */
    public function isLoggedIn() {
        return $this->skautis->isLoggedIn();
    }

    /**
     *
     * @param type $table - tabulka v DB skautisu
     * @param type $id - např. ID_EventGeneral, NULL = oveření nad celou tabulkou
     * @param type $ID_Action - id ověřované akce - např EV_EventGeneral_UPDATE
     * @return BOOL|stdClass|array
     */
    public function actionVerify($table, $id = NULL, $ID_Action = NULL) {
        $res = $this->skautis->user->ActionVerify(array(
            "ID" => $id,
            "ID_Table" => $table,
            "ID_Action" => $ID_Action,
        ));
        if ($ID_Action !== NULL) { //pokud je zadána konrétní funkce pro ověřování, tak se vrací BOOL
            if ($res instanceof \stdClass) {
                return false;
            }
            if (is_array($res)) {
                return true;
            }
        }
        if (is_array($res)) {
            $tmp = array();
            foreach ($res as $v) {
                $tmp[$v->ID] = $v;
            }
            return $tmp;
        }
        return $res;
    }

}