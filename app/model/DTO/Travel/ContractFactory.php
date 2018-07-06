<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use Model\Travel\Contract as ContractEntity;
use Nette\StaticClass;

final class ContractFactory
{
    use StaticClass;

    public static function create(ContractEntity $contract) : Contract
    {
        return new Contract(
            $contract->getId(),
            $contract->getPassenger(),
            $contract->getUnitId(),
            $contract->getUnitRepresentative(),
            $contract->getSince(),
            $contract->getUntil(),
            $contract->getTemplateVersion()
        );
    }
}
