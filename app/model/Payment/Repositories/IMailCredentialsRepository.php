<?php

namespace Model\Payment\Repositories;

use Model\Payment\MailCredentials;
use Model\Payment\MailCredentialsNotFound;

interface IMailCredentialsRepository
{

    /**
     * @throws MailCredentialsNotFound
     */
    public function find(int $id): MailCredentials;

    /**
     * @param int[]
     * @return MailCredentials[]
     */
    public function findByUnits(array $unitIds): array;

}
