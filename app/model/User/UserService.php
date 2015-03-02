<?php

namespace Model;

use \Nette\Utils\Strings;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class UserService extends BaseService {

    /**
     * varcí ID role aktuálně přihlášeného uživatele
     * @return type 
     */
    public function getRoleId() {
        return $this->skautis->getUser()->getRoleId();
    }

    /**
     * vrací pole 
     * @return array všech dostupných rolí přihlášeného uživatele
     */
    public function getAllSkautisRoles($activeOnly = true) {
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
        $response = $this->skautis->user->LoginUpdate(array("ID_UserRole" => $id, "ID" => $this->skautis->getUser()->getLoginId()));
        if ($response) {
            $this->skautis->getUser()->updateLoginData(NULL, $id, $response->ID_Unit);
        }
    }

    /**
     * informace o aktuálně přihlášené roli
     * @return boolean
     */
    public function getActualRole() {
        foreach ($this->getAllSkautisRoles() as $r) {
            if ($r->ID == $this->getRoleId()) {
                return $r;
            }
        }
        return FALSE;
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
     * @return boolean 
     */
    public function isLoggedIn() {
        return $this->skautis->getUser()->isLoggedIn();
    }
    
    public function updateLogoutTime(){
        return $this->skautis->getUser()->updateLogoutTime()->getLogoutDate();
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

    public function getAccessArrays(UnitService $us) {
        $r = $this->getActualRole();
        if (isset($r->Key)) {
            $unitIds = Strings::endsWith($r->Key, "Stredisko") || Strings::endsWith($r->Key, "Oddil") ? $us->getAllUnder($r->ID_Unit) : array($r->ID_Unit => $us->getDetail($r->ID_Unit));
            if (Strings::startsWith($r->Key, "cinovnik")) {
                return array(
                    self::ACCESS_READ => $unitIds,
                    self::ACCESS_EDIT => array()
                );
            } elseif (Strings::startsWith($r->Key, "vedouci") || Strings::startsWith($r->Key, "hospodar")) {
                return array(
                    self::ACCESS_READ => $unitIds,
                    self::ACCESS_EDIT => $unitIds
                );
            }
        }
        return array(
            self::ACCESS_READ => array(),
            self::ACCESS_EDIT => array()
        );
    }
    
    /**
     * vrací adresu skautisu např.: https://is.skaut.cz/
     * @return string
     */
    public function getSkautisUrl(){
        return $this->skautis->getConfig()->getBaseUrl();
    }

}
