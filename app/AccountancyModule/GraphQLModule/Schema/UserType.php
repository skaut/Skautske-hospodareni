<?php

namespace App\AccountancyModule\GraphQLModule\Schema;

use Youshido\GraphQL\Type\ListType\ListType;
use Youshido\GraphQL\Type\Object\AbstractObjectType;
use Youshido\GraphQL\Type\Scalar\IntType;

class UserType extends AbstractObjectType
{

    public function build($config): void
    {
        $this->addField('roles', new ListType(new User\RoleType()));
        $this->addField('activeRoleId', new IntType());
    }

}
