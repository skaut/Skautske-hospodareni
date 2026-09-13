<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Log;

use App\Model\User\SkautisRole;
use LogicException;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use stdClass;

use function array_map;
use function sprintf;

final class UserContextProvider
{
    public function __construct(private User $user)
    {
    }

    /** @return array<string, mixed> */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function getUserData(): ?array
    {
        $identity = $this->user->getIdentity();

        if ($identity === null) {
            return null;
        }

        if (! $identity instanceof SimpleIdentity) {
            throw new LogicException('Assertion failed.');
        }
        $currentRole = $identity->currentRole ?? null;

        if (! ($currentRole instanceof SkautisRole || $currentRole === null)) {
            throw new LogicException('Assertion failed.');
        }

        /** @var array<int, stdClass> $skautisRoles */
        $skautisRoles = $identity->skautisRoles ?? [];

        return [
            'id' => $identity->getId(),
            'currentRole' => $this->formatRole($currentRole),
            'roles' => array_map(
                function (stdClass $r) {
                    return sprintf('DisplayName: %s, Unit: %s, IsActive: %s', $r->DisplayName, $r->Unit, $r->IsActive);
                },
                $skautisRoles,
            ),
        ];
    }

    private function formatRole(?SkautisRole $role): string
    {
        if ($role === null) {
            return '';
        }

        return sprintf('DisplayName: %s, Unit: %s', $role->getName(), $role->getUnitName());
    }
}
