<?php

namespace App\AccountancyModule\Auth;


interface IAuthorizator
{

    /**
     * @param string[] $action [resource_class, action_name]
     */
    public function isAllowed(array $action, int $resourceId): bool;

}
