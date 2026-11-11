<?php

declare(strict_types=1);

namespace App\Model\Skautis\Common\Repositories;

use App\Model\Common\Repositories\IUserRepository;
use App\Model\Common\User;
use App\Model\Common\UserNotFound;
use Skaut\Skautis\User as SkautisUser;
use Skaut\Skautis\Wsdl\PermissionException;
use Skaut\Skautis\Wsdl\WebServiceInterface;
use stdClass;

final class UserRepository implements IUserRepository
{
    public function __construct(
        private WebServiceInterface $userWebService,
        private WebServiceInterface $orgWebService,
        private SkautisUser $skautisUser,
    ) {
    }

    public function find(int $id): User
    {
        return $this->findWithArguments(['ID' => $id]);
    }

    public function getCurrentUser(): User
    {
        return $this->findWithArguments([]);
    }

    public function findCurrentUser(): ?User
    {
        return $this->skautisUser->isLoggedIn() ? $this->getCurrentUser() : null;
    }

    /** @param mixed[] $arguments */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    private function findWithArguments(array $arguments): User
    {
        try {
            $user = $this->userWebService->UserDetail($arguments);
            if ($user instanceof stdClass) {
                $person = $this->orgWebService->PersonDetail(['ID' => $user->ID_Person]);

                return new User($user->ID, $user->Person, $person->Email);
            }
        } catch (PermissionException) {
        }

        throw new UserNotFound();
    }
}
