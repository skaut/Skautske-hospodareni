<?php

namespace Model\Auth;

interface IAuthorizator
{

    /**
     * Checks whether action is allowed
     *
     * Actions are either related to specific resource instance (i.e. specific Event) or completely
     * independent from instance (i.e. permission to create new Events)
     *
     * @param string[] $action [resource_class, action_name]
     */
    public function isAllowed(array $action, ?int $resourceId): bool;

}
