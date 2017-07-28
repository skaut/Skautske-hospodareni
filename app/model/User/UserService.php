<?php

namespace Model;

use Nette\Utils\Strings;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class UserService extends BaseService
{

    const SKAUTIS_CAMP_PREFIX = "EV_EventCamp";
    const SKAUTIS_GENERAL_PREFIX = "EV_EventGeneral";

    /**
     * varcí ID role aktuálně přihlášeného uživatele
     * @return int|NULL
     */
    public function getRoleId()
    {
        return $this->skautis->getUser()->getRoleId();
    }

    /**
     * vrací pole
     * @param bool $activeOnly
     * @return array všech dostupných rolí přihlášeného uživatele
     */
    public function getAllSkautisRoles($activeOnly = TRUE)
    {
        return $this->skautis->user->UserRoleAll(["ID_User" => $this->getUserDetail()->ID, "IsActive" => $activeOnly]);
    }

    public function getUserDetail()
    {
        $id = __FUNCTION__;
        if (!($res = $this->loadSes($id))) {
            $res = $this->saveSes($id, $this->skautis->user->UserDetail());
        }
        return $res;
    }

    /**
     * změní přihlášenou roli do skautISu
     * @param int $id
     */
    public function updateSkautISRole($id): void
    {
        $response = $this->skautis->user->LoginUpdate(["ID_UserRole" => $id, "ID" => $this->skautis->getUser()->getLoginId()]);
        if ($response) {
            $this->skautis->getUser()->updateLoginData(NULL, $id, $response->ID_Unit);
        }
    }

    /**
     * informace o aktuálně přihlášené roli
     * @return \stdClass|NULL
     */
    public function getActualRole()
    {
        foreach ($this->getAllSkautisRoles() as $r) {
            if ($r->ID == $this->getRoleId()) {
                return $r;
            }
        }

        return NULL;
    }

    /**
     * vrací kompletní seznam informací o přihlášené osobě
     * @return \stdClass
     */
    public function getPersonalDetail()
    {
        $user = $this->getUserDetail();
        $person = $this->skautis->org->personDetail((["ID" => $user->ID_Person]));
        return $person;
    }

    /**
     * kontroluje jestli je přihlášení platné
     * @return boolean
     */
    public function isLoggedIn()
    {
        return $this->skautis->getUser()->isLoggedIn();
    }

    public function updateLogoutTime()
    {
        return $this->skautis->getUser()->updateLogoutTime()->getLogoutDate();
    }

    /**
     * @deprecated Use UserService::getAvailableActions and search
     * @param string $table - tabulka v DB skautisu
     * @param int|NULL $id - např. ID_EventGeneral, NULL = oveření nad celou tabulkou
     * @param string $ID_Action - id ověřované akce - např EV_EventGeneral_UPDATE
     * @return bool|\stdClass|array
     */
    public function actionVerify($table, $id = NULL, $ID_Action = NULL)
    {
        $res = $this->skautis->user->ActionVerify([
            "ID" => $id,
            "ID_Table" => $table,
            "ID_Action" => $ID_Action,
        ]);
        if ($ID_Action !== NULL) { //pokud je zadána konrétní funkce pro ověřování, tak se vrací BOOL
            if ($res instanceof \stdClass) {
                return FALSE;
            }
            if (is_array($res)) {
                return TRUE;
            }
        }
        if (is_array($res)) {
            $tmp = [];
            foreach ($res as $v) {
                $tmp[$v->ID] = $v;
            }
            return $tmp;
        }
        return $res;
    }

    /**
     * Returns available actions for given resource
     * @return string[]
     */
    public function getAvailableActions(string $table, ?int $id = NULL): array
    {
        $result = $this->skautis->user->ActionVerify([
            "ID" => $id,
            "ID_Table" => $table,
            "ID_Action" => NULL,
        ]);

        if(!is_array($result)) {
            return [];
        }

        return array_map(function (\stdClass $value) {
            return (string)$value->ID;
        }, $result);
    }

    public function getAccessArrays(UnitService $us)
    {
        $r = $this->getActualRole();
        if ($r !== NULL && isset($r->Key)) {
            $unitIds = Strings::endsWith($r->Key, "Stredisko") || Strings::endsWith($r->Key, "Oddil") ? $us->getAllUnder($r->ID_Unit) : [$r->ID_Unit => $us->getDetail($r->ID_Unit)];
            if (Strings::startsWith($r->Key, "cinovnik")) {
                return [
                    self::ACCESS_READ => $unitIds,
                    self::ACCESS_EDIT => []
                ];
            } elseif (Strings::startsWith($r->Key, "vedouci") || Strings::startsWith($r->Key, "hospodar")) {
                return [
                    self::ACCESS_READ => $unitIds,
                    self::ACCESS_EDIT => $unitIds
                ];
            }
        }
        return [
            self::ACCESS_READ => [],
            self::ACCESS_EDIT => []
        ];
    }

    /**
     * vrací adresu skautisu např.: https://is.skaut.cz/
     */
    public function getSkautisUrl(): string
    {
        return $this->skautis->getConfig()->getBaseUrl();
    }

    public function IsEventEditable(int $id): bool
    {
        return (bool) $this->actionVerify(self::SKAUTIS_GENERAL_PREFIX, $id, self::SKAUTIS_GENERAL_PREFIX. "_UPDATE");
    }

    public function IsCampEditable(int $id): bool
    {
        $actions = $this->actionVerify(self::SKAUTIS_CAMP_PREFIX, $id);
        return(
            array_key_exists(self::SKAUTIS_CAMP_PREFIX . "_UPDATE", $actions) ||
            array_key_exists(self::SKAUTIS_CAMP_PREFIX . "_UPDATE_Real", $actions) ||
            array_key_exists(self::SKAUTIS_CAMP_PREFIX . "_UPDATE_RealTotalCostBeforeEnd", $actions)
        );
    }

}
