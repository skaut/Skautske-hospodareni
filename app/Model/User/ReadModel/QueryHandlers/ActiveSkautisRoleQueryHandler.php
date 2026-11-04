<?php

declare(strict_types=1);

namespace App\Model\User\ReadModel\QueryHandlers;

use App\Model\Skautis\Exception\MissingCurrentRole;
use App\Model\User\ReadModel\Queries\ActiveSkautisRoleQuery;
use App\Model\User\SkautisRole;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;

/**
 * Reads the role stored in the identity at login and on role change, so no SkautIS call is made.
 */
final class ActiveSkautisRoleQueryHandler
{
    public function __construct(private User $user)
    {
    }

    public function __invoke(ActiveSkautisRoleQuery $_x): SkautisRole
    {
        $identity = $this->user->getIdentity();
        $role = $identity instanceof SimpleIdentity ? $identity->currentRole ?? null : null;

        if ($role instanceof SkautisRole) {
            return $role;
        }

        throw new MissingCurrentRole();
    }
}
