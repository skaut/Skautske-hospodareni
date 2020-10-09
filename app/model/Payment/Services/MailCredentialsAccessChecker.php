<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Model\Payment\IUnitResolver;
use Model\Payment\MailCredentialsNotFound;
use Model\Payment\Repositories\IMailCredentialsRepository;

final class MailCredentialsAccessChecker implements IMailCredentialsAccessChecker
{
    private IMailCredentialsRepository $credentials;

    private IUnitResolver $unitResolver;

    public function __construct(IMailCredentialsRepository $credentials, IUnitResolver $unitResolver)
    {
        $this->credentials  = $credentials;
        $this->unitResolver = $unitResolver;
    }

    /**
     * @param int[] $unitIds
     *
     * @throws MailCredentialsNotFound
     */
    public function allUnitsHaveAccessToMailCredentials(array $unitIds, int $mailCredentialsId) : bool
    {
        $ownerUnitId = $this->credentials->find($mailCredentialsId)->getUnitId();

        foreach ($unitIds as $unitId) {
            if ($unitId === $ownerUnitId || $this->unitResolver->getOfficialUnitId($unitId) === $ownerUnitId) {
                return true;
            }
        }

        return false;
    }
}
