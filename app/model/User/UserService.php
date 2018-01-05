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
     */
    public function getRoleId(): ?int
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
     */
    public function isLoggedIn(): bool
    {
        return $this->skautis->getUser()->isLoggedIn();
    }

    public function updateLogoutTime()
    {
        return $this->skautis->getUser()->updateLogoutTime()->getLogoutDate();
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

}
