<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Model\Common\Services\QueryBus;
use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\UnitService;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use Nette\Application\BadRequestException;
use Skautis\Skautis;
use Skautis\User;
use stdClass;

use function array_key_exists;

class UserService
{
    public const ACCESS_READ = 'read';
    public const ACCESS_EDIT = 'edit';

    /**
     * krátkodobé lokální úložiště pro ukládání odpovědí ze skautISU.
     *
     * @var mixed[]
     */
    private static array $storage = [];

    /**
     * slouží pro komunikaci se skautISem.
     */
    protected Skautis $skautis;

    public function __construct(Skautis $skautis, private QueryBus $queryBus)
    {
        $this->skautis = $skautis;
    }

    /**
     * varcí ID role aktuálně přihlášeného uživatele.
     */
    public function getRoleId(): ?int
    {
        return $this->skautis->getUser()->getRoleId();
    }

    /**
     * Returns all available roles for current user.
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
        $id = __FUNCTION__;
        $res = $this->loadSes($id);
        if (! $res) {
            $res = $this->saveSes($id, $this->skautis->user->UserDetail());
        }

        return $res;
    }

    public function getUser(): User
    {
        return $this->skautis->getUser();
    }

    /**
     * změní přihlášenou roli do skautISu.
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
     * informace o aktuálně přihlášené roli.
     *
     * @internal  Use query bus with ActiveSkautisRoleQuery
     *
     * @see ActiveSkautisRoleQuery
     *
     * @param stdClass[]|null $roles  roles already loaded by getAllSkautisRoles(), saves a SkautIS call
     * @param int|null        $roleId another role than the active one, e.g. the one a switch goes to
     */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function getActualRole(?array $roles = null, ?int $roleId = null): ?SkautisRole
    {
        $roleId ??= $this->getRoleId();
        foreach ($roles ?? $this->getAllSkautisRoles() as $r) {
            if ($r->ID === $roleId) {
                return new SkautisRole($r->Key ?? '', $r->DisplayName, $r->ID_Unit, $r->Unit);
            }
        }

        return null;
    }

    /**
     * vrací kompletní seznam informací o přihlášené osobě.
     */
    public function getPersonalDetail(): stdClass
    {
        $user = $this->getUserDetail();

        return $this->skautis->org->personDetail(['ID' => $user->ID_Person]);
    }

    /**
     * kontroluje jestli je přihlášení platné.
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
     * @param stdClass[]|null $roles  roles already loaded by getAllSkautisRoles(), saves a SkautIS call
     * @param int|null        $roleId the role to compute access for, the active one by default
     *
     * @return mixed[]
     *
     * @throws BadRequestException
     */
    public function getAccessArrays(UnitService $us, ?array $roles = null, ?int $roleId = null): array
    {
        $role = $this->getActualRole($roles, $roleId);

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
     * vrací adresu skautisu např.: https://is.skaut.cz/.
     */
    public function getSkautisUrl(): string
    {
        return $this->skautis->getConfig()->getBaseUrl();
    }

    /**
     * ukládá $val do lokálního úložiště.
     */
    private function saveSes(mixed $id, mixed $val): mixed
    {
        return self::$storage[$id] = $val;
    }

    /**
     * vrací objekt z lokálního úložiště.
     */
    private function loadSes(string|int $id): mixed
    {
        if (array_key_exists($id, self::$storage)) {
            return self::$storage[$id];
        }

        return false;
    }
}
