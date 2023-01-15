<?php

declare(strict_types=1);

namespace Model\DTO\Payment;

use Model\Payment\Group as GroupEntity;

class GroupFactory
{
    public static function create(GroupEntity $group): Group
    {
        $object = $group->getObject();

        return new Group(
            $group->getId(),
            $object?->getType()->getValue(),
            $group->getUnitIds(),
            $object?->getId(),
            $group->getName(),
            $group->getDefaultAmount(),
            $group->getDueDate(),
            $group->getConstantSymbol(),
            $group->getPaymentDefaults()->getNextVariableSymbol(),
            $group->getState(),
            $group->getOauthId(),
            $group->getNote(),
            $group->getBankAccountId(),
        );
    }
}
