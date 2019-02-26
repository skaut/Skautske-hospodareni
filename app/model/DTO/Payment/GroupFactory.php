<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Cake\Chronos\Date;
use Model\Payment\Group as GroupEntity;

class GroupFactory
{
    public static function create(GroupEntity $group) : Group
    {
        $object = $group->getObject();

        return new Group(
            $group->getId(),
            $object !== null ? $object->getType()->getValue() : null,
            $group->getUnitId(),
            $object !== null ? $object->getId() : null,
            $group->getName(),
            $group->getDefaultAmount(),
            Date::instance($group->getDueDate()),
            $group->getConstantSymbol(),
            $group->getNextVariableSymbol(),
            $group->getState(),
            $group->getSmtpId(),
            $group->getNote(),
            $group->getBankAccountId()
        );
    }
}
