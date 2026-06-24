<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\DTO\Payment\MemberEmail;
use App\Model\Payment\ReadModel\Queries\MemberEmailsQuery;
use App\Model\Payment\Repositories\IMemberEmailRepository;

final class MemberEmailsQueryHandler
{
    public function __construct(private IMemberEmailRepository $emails)
    {
    }

    /** @return MemberEmail[] */
    public function __invoke(MemberEmailsQuery $query): array
    {
        return $this->emails->findByMember($query->getMemberId());
    }
}
