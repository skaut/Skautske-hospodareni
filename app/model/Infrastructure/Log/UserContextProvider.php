<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log;

use Model\User\SkautisRole;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use stdClass;

use function array_map;
use function assert;
use function sprintf;

final class UserContextProvider
{
    public function __construct(private User $user)
    {
    }

    /** @return array<string, mixed> */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function getUserData(): array|null
    {
        $identity = $this->user->getIdentity();

        if ($identity === null) {
            return null;
        }

        assert($identity instanceof SimpleIdentity);

        $currentRole = $identity->currentRole ?? null;

        assert($currentRole instanceof SkautisRole || $currentRole === null);

        return [
            'id' => $identity->getId(),
            'currentRole' => $this->formatRole($currentRole),
            'roles' => array_map(
                function (stdClass $r) {
                    return sprintf('DisplayName: %s, Unit: %s, IsActive: %s', $r->DisplayName, $r->Unit, $r->IsActive);
                },
                $identity->getRoles(),
            ),
        ];
    }

    private function formatRole(SkautisRole|null $role): string
    {
        if ($role === null) {
            return '';
        }

        return sprintf('DisplayName: %s, Unit: %s', $role->getName(), $role->getUnitName());
    }
}
