<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Model\Payment\MailCredentialsNotFound;

interface IMailCredentialsAccessChecker
{
    /**
     * @param int[] $unitIds
     *
     * @throws MailCredentialsNotFound
     */
    public function allUnitsHaveAccessToMailCredentials(array $unitIds, int $mailCredentialsId) : bool;
}
