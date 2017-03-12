<?php

namespace Model\DTO\Payment;

use Model\Payment\Group as GroupEntity;

class GroupFactory
{

    public static function create(GroupEntity $group): Group
    {
        return new Group(
            $group->getId(),
            $group->getUnitId(),
            $group->getName(),
            $group->getDefaultAmount(),
            $group->getDueDate(),
            $group->getConstantSymbol(),
            $group->getNextVariableSymbol(),
            $group->getEmailTemplate(),
            $group->getSmtpId()
        );
    }

}
