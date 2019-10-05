<?php

declare(strict_types=1);

namespace Stubs;

use Model\Payment\Services\IMailCredentialsAccessChecker;

final class MailCredentialsAccessCheckerStub implements IMailCredentialsAccessChecker
{
    /**
     * @param int[] $unitIds
     */
    public function allUnitsHaveAccessToMailCredentials(array $unitIds, int $mailCredentialsId) : bool
    {
        return true;
    }
}
