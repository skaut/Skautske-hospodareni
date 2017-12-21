<?php

namespace App\AccountancyModule\GraphQLModule\Schema\User;

use Youshido\GraphQL\Type\Object\AbstractObjectType;
use Youshido\GraphQL\Type\Scalar\IntType;
use Youshido\GraphQL\Type\Scalar\StringType;

class RoleType extends AbstractObjectType
{

    public function build($config)
    {
        $this->addField('id', new IntType());
        $this->addField('name', new StringType());
    }

}
