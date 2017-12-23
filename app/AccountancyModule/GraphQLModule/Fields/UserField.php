<?php

namespace App\AccountancyModule\GraphQLModule\Fields;

use App\AccountancyModule\GraphQLModule\Schema\UserType;
use Model\UserService;
use Nette\Application\LinkGenerator;
use Nette\Security\User;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Field\AbstractField;

class UserField extends AbstractField
{

    /** @var UserService */
    private $userService;

    /** @var User */
    private $user;

    /** @var LinkGenerator */
    private $linkGenerator;

    public function __construct(UserService $userService, User $user, LinkGenerator $linkGenerator)
    {
        parent::__construct();
        $this->userService = $userService;
        $this->user = $user;
        $this->linkGenerator = $linkGenerator;
    }

    public function getType(): UserType
    {
        return new UserType();
    }

    public function getName(): string
    {
        return 'user';
    }

    public function resolve($value, array $args, ResolveInfo $info): array
    {
        if ($this->user->isLoggedIn() === FALSE) {
            return [
                'loggedIn' => FALSE,
                'loginLink' => $this->linkGenerator->link('Auth:logOnSkautIs'),
                'roles' => [],
            ];
        }

        $roles = array_map(function (\stdClass $role) {
            return [
                'id' => $role->ID,
                'name' => ($role->RegistrationNumber ? ($role->RegistrationNumber . ' - ') : '') . $role->Role,
            ];
        }, $this->userService->getAllSkautisRoles());

        return [
            'loggedIn' => TRUE,
            'logoutLink' => $this->linkGenerator->link('Auth:logoutSis'),
            'activeRoleId' => $this->userService->getRoleId(),
            'roles' => $roles,
        ];
    }

}
