<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Model\DTO\Payment\GroupEmail;
use Model\Payment\GroupNotFoundException;
use Model\Payment\ReadModel\Queries\GroupEmailQuery;
use Model\Payment\Repositories\IGroupRepository;

final class GroupEmailHandler
{

    /** @var IGroupRepository */
    private $groups;

    public function __construct(IGroupRepository $groups)
    {
        $this->groups = $groups;
    }

    public function handle(GroupEmailQuery $query): ?GroupEmail
    {
        try {
            $group = $this->groups->find($query->getGroupId());
            $template = $group->getEmailTemplate($query->getEmailType());

            if ($template === NULL) {
                return NULL;
            }

            return new GroupEmail($template, $group->isEmailEnabled($query->getEmailType()));
        } catch (GroupNotFoundException $e) {
            return NULL;
        }
    }

}
