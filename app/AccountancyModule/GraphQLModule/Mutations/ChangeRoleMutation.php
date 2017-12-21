<?php

namespace App\AccountancyModule\GraphQLModule\Mutations;

use Model\UserService;
use Nette\Security\User;
use Youshido\GraphQL\Config\Field\FieldConfig;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Field\AbstractField;
use Youshido\GraphQL\Type\NonNullType;
use Youshido\GraphQL\Type\Scalar\BooleanType;
use Youshido\GraphQL\Type\Scalar\IntType;

class ChangeRoleMutation extends AbstractField
{

    /** @var UserService */
    private $userService;

    /** @var User */
    private $user;

    public function __construct(UserService $userService, User $user)
    {
        parent::__construct();
        $this->userService = $userService;
        $this->user = $user;
    }

    public function build(FieldConfig $config)
    {
        $config->addArgument('roleId', new NonNullType(new IntType()));
    }

    public function getName(): string
    {
        return 'changeRole';
    }

    public function getType(): BooleanType
    {
        return new BooleanType();
    }

    public function resolve($value, array $args, ResolveInfo $info)
    {
        if ( ! $this->user->isLoggedIn()) {
            throw new \Exception('User not logged in');
        }

        $this->userService->updateSkautISRole($args['roleId']);

        return TRUE;
    }

}
