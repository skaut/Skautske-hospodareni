<?php

declare(strict_types=1);

namespace Model;

use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\SkautisRole;
use Nette\Application\BadRequestException;
use stdClass;

class UserService extends BaseService
{
    /**
     * varcí ID role aktuálně přihlášeného uživatele
     */
    public function getRoleId() : ?int
    {
        return $this->skautis->getUser()->getRoleId();
    }

    /**
     * Returns all available roles for current user
     *
     * @return stdClass[]
     */
    public function getAllSkautisRoles(bool $activeOnly = true) : array
    {
        $res = $this->skautis->user->UserRoleAll(['ID_User' => $this->getUserDetail()->ID, 'IsActive' => $activeOnly]);

        return $res instanceof stdClass ? [] : $res;
    }

    public function getUserDetail() : stdClass
    {
        $id  = __FUNCTION__;
        $res = $this->loadSes($id);
        if (! $res) {
            $res = $this->saveSes($id, $this->skautis->user->UserDetail());
        }

        return $res;
    }

    /**
     * změní přihlášenou roli do skautISu
     */
    public function updateSkautISRole(int $id) : void
    {
        $response = $this->skautis->user->LoginUpdate(['ID_UserRole' => $id, 'ID' => $this->skautis->getUser()->getLoginId()]);
        if (! $response) {
            return;
        }

        $this->skautis->getUser()->updateLoginData(null, $id, $response->ID_Unit);
    }

    /**
     * informace o aktuálně přihlášené roli
     *
     * @internal  Use query bus with ActiveSkautisRoleQuery
     *
     * @see ActiveSkautisRoleQuery
     */
    public function getActualRole() : ?SkautisRole
    {
        foreach ($this->getAllSkautisRoles() as $r) {
            if ($r->ID === $this->getRoleId()) {
                return new SkautisRole($r->Key ?? '', $r->ID_Unit);
            }
        }

        return null;
    }

    /**
     * vrací kompletní seznam informací o přihlášené osobě
     */
    public function getPersonalDetail() : stdClass
    {
        $user = $this->getUserDetail();

        return $this->skautis->org->personDetail((['ID' => $user->ID_Person]));
    }

    /**
     * kontroluje jestli je přihlášení platné
     */
    public function isLoggedIn() : bool
    {
        return $this->skautis->getUser()->isLoggedIn();
    }

    public function updateLogoutTime() : void
    {
        $this->skautis->getUser()->updateLogoutTime()->getLogoutDate();
    }

    /**
     * @return mixed[]
     *
     * @throws BadRequestException
     */
    public function getAccessArrays(UnitService $us) : array
    {
        $role = $this->getActualRole();

        if ($role !== null) {
            $unitIds = $role->isBasicUnit() || $role->isTroop()
                ? $us->getAllUnder($role->getUnitId())
                : [$role->getUnitId() => $us->getDetail($role->getUnitId())];

            if ($role->isOfficer()) {
                return [
                    self::ACCESS_READ => $unitIds,
                    self::ACCESS_EDIT => [],
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
    public function getSkautisUrl() : string
    {
        return $this->skautis->getConfig()->getBaseUrl();
    }
}
