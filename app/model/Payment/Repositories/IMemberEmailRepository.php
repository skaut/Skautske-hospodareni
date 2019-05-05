<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

interface IMemberEmailRepository
{
    /**
     * @return array<string, string> email address => email label
     */
    public function findByMember(int $memberId) : array;
}
