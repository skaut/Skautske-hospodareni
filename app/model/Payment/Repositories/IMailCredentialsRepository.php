<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

use Model\Payment\MailCredentials;
use Model\Payment\MailCredentialsNotFound;

interface IMailCredentialsRepository
{
    /**
     * @throws MailCredentialsNotFound
     */
    public function find(int $id) : MailCredentials;

    /**
     * @param int[] $unitIds
     *
     * @return array<int, MailCredentials[]>
     */
    public function findByUnits(array $unitIds) : array;

    public function remove(MailCredentials $credentials) : void;

    public function save(MailCredentials $credentials) : void;
}
