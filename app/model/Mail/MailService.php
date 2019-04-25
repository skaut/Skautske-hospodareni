<?php

declare(strict_types=1);

namespace Model;

use Model\DTO\Payment\Mail;
use Model\DTO\Payment\MailFactory;
use Model\Payment\IUnitResolver;
use Model\Payment\MailCredentials;
use Model\Payment\MailCredentialsNotFound;
use Model\Payment\Repositories\IMailCredentialsRepository;
use function array_map;
use function array_merge;

class MailService
{
    /** @var IMailCredentialsRepository */
    private $credentials;

    /** @var IUnitResolver */
    private $unitResolver;

    public function __construct(IMailCredentialsRepository $credentials, IUnitResolver $unitResolver)
    {
        $this->credentials  = $credentials;
        $this->unitResolver = $unitResolver;
    }

    public function get(int $id) : ?Mail
    {
        try {
            return MailFactory::create(
                $this->credentials->find($id)
            );
        } catch (MailCredentialsNotFound $e) {
            return null;
        }
    }

    /**
     * @return Mail[]
     */
    public function getAll(int $unitId) : array
    {
        $mails = $this->findForUnit($unitId);

        return array_map([MailFactory::class, 'create'], $mails);
    }

    /**
     * @return string[]
     */
    public function getPairs(int $unitId) : array
    {
        $pairs = [];
        foreach ($this->findForUnit($unitId) as $credentials) {
            $pairs[$credentials->getId()] = $credentials->getUsername();
        }

        return $pairs;
    }

    /**
     * @return MailCredentials[]
     */
    private function findForUnit(int $unitId) : array
    {
        $units  = [$unitId, $this->unitResolver->getOfficialUnitId($unitId)];
        $byUnit = $this->credentials->findByUnits($units);

        return array_merge(...$byUnit);
    }
}
