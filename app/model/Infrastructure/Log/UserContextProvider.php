<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log;

use Nette\Security\User;
use stdClass;
use function array_map;
use function sprintf;

final class UserContextProvider
{
    /** @var User */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserData() : ?array
    {
        $identity = $this->user->getIdentity();

        if ($identity === null) {
            return null;
        }

        return [
            'id' => $identity->getId(),
            'roles' => array_map(
                function (stdClass $r) {
                    return sprintf('DisplayName: %s, Unit: %s, IsActive: %s', $r->DisplayName, $r->Unit, $r->IsActive);
                },
                $identity->getRoles()
            ),
        ];
    }
}
