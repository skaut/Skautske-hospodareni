<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

interface IMemberEmailRepository
{
    /**
     * @return string[]
     */
    public function findByMember(int $memberId) : array;
}
