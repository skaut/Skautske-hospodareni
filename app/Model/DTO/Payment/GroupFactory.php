<?php

declare(strict_types=1);

namespace App\Model\DTO\Payment;

use App\Model\Payment\Group as GroupEntity;

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
            $group->isRemindersEnabled(),
            $group->isAutomaticPairingEnabled(),
            $group->getPairingDaysBack(),
            $group->getCreatedAt(),
        );
    }
}
