<?php

namespace App\AccountancyModule\GraphQLModule\Schema;

use Youshido\GraphQL\Type\ListType\ListType;
use Youshido\GraphQL\Type\NonNullType;
use Youshido\GraphQL\Type\Object\AbstractObjectType;
use Youshido\GraphQL\Type\Scalar\BooleanType;
use Youshido\GraphQL\Type\Scalar\IntType;
use Youshido\GraphQL\Type\Scalar\StringType;

class UserType extends AbstractObjectType
{

    public function build($config): void
    {
        $this->addField('loggedIn', new NonNullType(new BooleanType()));
        $this->addField('loginLink', new StringType());
        $this->addField('logoutLink', new StringType());
        $this->addField('roles', new ListType(new User\RoleType()));
        $this->addField('activeRoleId', new IntType());
    }

}
