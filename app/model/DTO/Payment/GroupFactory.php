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
    public static function create(GroupEntity $group, array $stats = []): Group
    {
        return new Group(
            $group->getId(),
            $group->getType(),
            $group->getUnitId(),
            $group->getSkautisId(),
            $group->getName(),
            $group->getDefaultAmount(),
            $group->getDueDate(),
            $group->getConstantSymbol(),
            $group->getNextVariableSymbol(),
            $group->getState(),
            $group->getEmailTemplate(),
            $group->getSmtpId(),
            $group->getNote(),
            $stats
        );
    }

}
