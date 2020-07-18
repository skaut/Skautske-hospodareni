<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Model\Common\Registration;
use Model\Common\Repositories\IRegistrationRepository;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\ReadModel\Queries\RegistrationWithoutGroupQuery;
use Model\Payment\Repositories\IGroupRepository;
use function count;

final class RegistrationWithoutGroupQueryHandler
{
    private IRegistrationRepository $registrations;

    private IGroupRepository $groups;

    public function __construct(IRegistrationRepository $registrations, IGroupRepository $groups)
    {
        $this->registrations = $registrations;
        $this->groups        = $groups;
    }

    public function __invoke(RegistrationWithoutGroupQuery $query) : ?Registration
    {
        $registrations = $this->registrations->findByUnit($query->getUnitId());

        if (count($registrations) === 0) {
            return null;
        }

        $lastRegistration = $registrations[0];

        $groups = $this->groups->findBySkautisEntities(
            new SkautisEntity($lastRegistration->getId(), Type::get(Type::REGISTRATION))
        );

        return count($groups) === 0 ? $lastRegistration : null;
    }
}
