<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\DTO\Payment\GroupEmail;
use App\Model\Payment\GroupNotFound;
use App\Model\Payment\ReadModel\Queries\GroupEmailQuery;
use App\Model\Payment\Repositories\IGroupRepository;

final class GroupEmailQueryHandler
{
    public function __construct(private IGroupRepository $groups)
    {
    }

    public function __invoke(GroupEmailQuery $query): ?GroupEmail
    {
        try {
            $group = $this->groups->find($query->getGroupId());
            $template = $group->getEmailTemplate($query->getEmailType());

            if ($template === null) {
                return null;
            }

            return new GroupEmail($template, $group->isEmailEnabled($query->getEmailType()));
        } catch (GroupNotFound) {
            return null;
        }
    }
}
