<?php

namespace Model;

use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\SkautisRole;
use Nette\Utils\Strings;

class UserService extends BaseService
{

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
     * @internal  Use query bus with ActiveSkautisRoleQuery
     * @see ActiveSkautisRoleQuery
     */
    public function getActualRole(): ?SkautisRole
    {
        foreach ($this->getAllSkautisRoles() as $r) {
            if (isset($r->Key) && $r->ID === $this->getRoleId()) {
                return new SkautisRole($r->Key, $r->ID_Unit);
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

    public function getAccessArrays(UnitService $us): array
    {
        $role = $this->getActualRole();

        if ($role !== NULL) {
            $unitIds = $role->isBasicUnit() || $role->isTroop()
                ? $us->getAllUnder($role->getUnitId())
                : [$role->getUnitId() => $us->getDetail($role->getUnitId())];

            if ($role->isOfficer()) {
                return [
                    self::ACCESS_READ => $unitIds,
                    self::ACCESS_EDIT => []
                ];
            }

            if ($role->isLeader() || $role->isAccountant()) {
                return [
                    self::ACCESS_READ => $unitIds,
                    self::ACCESS_EDIT => $unitIds,
                ];
            }
        }

        return [
            self::ACCESS_READ => [],
            self::ACCESS_EDIT => [],
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
