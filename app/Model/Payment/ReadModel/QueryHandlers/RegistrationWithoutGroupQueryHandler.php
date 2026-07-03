<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Common\Registration;
use App\Model\Common\Repositories\IRegistrationRepository;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\Group\Type;
use App\Model\Payment\ReadModel\Queries\RegistrationWithoutGroupQuery;
use App\Model\Payment\Repositories\IGroupRepository;

use function count;

final class RegistrationWithoutGroupQueryHandler
{
    public function __construct(private IRegistrationRepository $registrations, private IGroupRepository $groups)
    {
    }

    public function __invoke(RegistrationWithoutGroupQuery $query): ?Registration
    {
        $registrations = $this->registrations->findByUnit($query->getUnitId());

        if (count($registrations) === 0) {
            return null;
        }

        $lastRegistration = $registrations[0];

        $groups = $this->groups->findBySkautisEntities(
            new SkautisEntity($lastRegistration->getId(), Type::get(Type::REGISTRATION)),
        );

        return count($groups) === 0 ? $lastRegistration : null;
    }
}
