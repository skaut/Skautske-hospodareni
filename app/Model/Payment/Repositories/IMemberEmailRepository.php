<?php

declare(strict_types=1);

namespace App\Model\Payment\Repositories;

use App\Model\DTO\Payment\MemberEmail;

interface IMemberEmailRepository
{
    /** @return MemberEmail[] */
    public function findByMember(int $memberId): array;
}
