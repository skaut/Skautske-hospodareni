<?php

declare(strict_types=1);

namespace Model\Skautis\Common\Repositories;

use Model\Common\Repositories\IUserRepository;
use Model\Common\User;
use Model\Common\UserNotFound;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;
use stdClass;

final class UserRepository implements IUserRepository
{
    private WebServiceInterface $userWebService;

    private WebServiceInterface $orgWebService;

    public function __construct(WebServiceInterface $userWebService, WebServiceInterface $orgWebService)
    {
        $this->userWebService = $userWebService;
        $this->orgWebService  = $orgWebService;
    }

    public function find(int $id) : User
    {
        return $this->findWithArguments(['ID' => $id]);
    }

    public function getCurrentUser() : User
    {
        return $this->findWithArguments([]);
    }

    /** @param mixed[] $arguments */
    private function findWithArguments(array $arguments) : User
    {
        try {
            $user = $this->userWebService->UserDetail($arguments);
            if ($user instanceof stdClass) {
                $person = $this->orgWebService->PersonDetail(['ID' => $user->ID_Person]);

                return new User($user->ID, $user->Person, $person->Email);
            }
        } catch (PermissionException $e) {
        }

        throw new UserNotFound();
    }
}
