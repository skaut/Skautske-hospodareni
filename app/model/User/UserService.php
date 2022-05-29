<?php

declare(strict_types=1);

namespace Model;

use Model\Common\Services\QueryBus;
use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Model\User\SkautisRole;
use Nette\Application\BadRequestException;
use Skautis\Skautis;
use stdClass;

use function array_key_exists;

class UserService
{
    public const ACCESS_READ = 'read';
    public const ACCESS_EDIT = 'edit';

    private QueryBus $queryBus;

    /**
     * krátkodobé lokální úložiště pro ukládání odpovědí ze skautISU
     *
     * @var mixed[]
     */
    private static array $storage = [];


    /**
     * slouží pro komunikaci se skautISem
     */
    protected Skautis $skautis;

    public function __construct(Skautis $skautis, QueryBus $queryBus)
    {
        $this->skautis  = $skautis;
        $this->queryBus = $queryBus;
    }

    /**
     * varcí ID role aktuálně přihlášeného uživatele
     */
    public function getRoleId(): ?int
    {
        return $this->skautis->getUser()->getRoleId();
    }

    /**
     * Returns all available roles for current user
     *
     * @return stdClass[]
     */
    public function getAllSkautisRoles(bool $activeOnly = true): array
    {
        $res = $this->skautis->user->UserRoleAll(['ID_User' => $this->getUserDetail()->ID, 'IsActive' => $activeOnly]);

        return $res instanceof stdClass ? [] : $res;
    }

    public function getUserDetail(): stdClass
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
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function updateSkautISRole(int $id): void
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
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function getActualRole(): ?SkautisRole
    {
        foreach ($this->getAllSkautisRoles() as $r) {
            if ($r->ID === $this->getRoleId()) {
                return new SkautisRole($r->Key ?? '', $r->DisplayName, $r->ID_Unit, $r->Unit);
            }
        }

        return null;
    }

    /**
     * vrací kompletní seznam informací o přihlášené osobě
     */
    public function getPersonalDetail(): stdClass
    {
        $user = $this->getUserDetail();

        return $this->skautis->org->personDetail((['ID' => $user->ID_Person]));
    }

    /**
     * kontroluje jestli je přihlášení platné
     */
    public function isLoggedIn(): bool
    {
        return $this->skautis->getUser()->isLoggedIn();
    }

    public function updateLogoutTime(): void
    {
        $this->skautis->getUser()->updateLogoutTime()->getLogoutDate();
    }

    /**
     * @return mixed[]
     *
     * @throws BadRequestException
     */
    public function getAccessArrays(UnitService $us): array
    {
        $role = $this->getActualRole();

        if ($role !== null) {
            $unitIds = $role->isBasicUnit() || $role->isTroop()
                ? $us->getAllUnder($role->getUnitId())
                : [$role->getUnitId() => $this->queryBus->handle(new UnitQuery($role->getUnitId()))];

            if ($role->isOfficer()) {
                return [
                    self::ACCESS_READ => $unitIds,
                    self::ACCESS_EDIT => [],
                ];
            }

            if ($role->isLeader() || $role->isAccountant() || $role->isEventManager()) {
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

    /**
     * ukládá $val do lokálního úložiště
     *
     * @param mixed $id
     * @param mixed $val
     *
     * @return mixed
     */
    private function saveSes($id, $val)
    {
        return self::$storage[$id] = $val;
    }

    /**
     * vrací objekt z lokálního úložiště
     *
     * @param string|int $id
     *
     * @return mixed | FALSE
     */
    private function loadSes($id)
    {
        if (array_key_exists($id, self::$storage)) {
            return self::$storage[$id];
        }

        return false;
    }
}
