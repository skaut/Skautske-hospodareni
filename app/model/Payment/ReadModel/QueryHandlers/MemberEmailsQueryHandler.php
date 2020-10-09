<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use Model\Payment\Repositories\IMemberEmailRepository;

final class MemberEmailsQueryHandler
{
    private IMemberEmailRepository $emails;

    public function __construct(IMemberEmailRepository $emails)
    {
        $this->emails = $emails;
    }

    /**
     * @return array<string, string> email address => email label
     */
    public function __invoke(MemberEmailsQuery $query) : array
    {
        return $this->emails->findByMember($query->getMemberId());
    }
}
