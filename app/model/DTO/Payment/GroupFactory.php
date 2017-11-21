<?php

namespace Model\DTO\Payment;

use Model\Payment\Group as GroupEntity;

class GroupFactory
{

    /**
     * @param GroupEntity $group
     * @param Summary[] $stats
     * @return Group
     */
    public static function create(GroupEntity $group): Group
    {
        $object = $group->getObject();

        return new Group(
            $group->getId(),
            $object !== NULL ? $object->getType()->getValue() : NULL,
            $group->getUnitId(),
            $object !== NULL ? $object->getId() : NULL,
            $group->getName(),
            $group->getDefaultAmount(),
            $group->getDueDate(),
            $group->getConstantSymbol(),
            $group->getNextVariableSymbol(),
            $group->getState(),
            $group->getEmailTemplate(),
            $group->getSmtpId(),
            $group->getNote(),
            $group->getBankAccountId()
        );
    }

}
