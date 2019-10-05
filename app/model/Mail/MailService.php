<?php

declare(strict_types=1);

namespace Model;

use Model\DTO\Payment\Mail;
use Model\DTO\Payment\MailFactory;
use Model\Payment\IUnitResolver;
use Model\Payment\MailCredentials;
use Model\Payment\MailCredentialsNotFound;
use Model\Payment\Repositories\IMailCredentialsRepository;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;

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
     * @param int[] $unitIds
     *
     * @return Mail[]
     */
    public function getAll(array $unitIds) : array
    {
        $mails = $this->findForUnits($unitIds);

        return array_map([MailFactory::class, 'create'], array_merge([], ...$mails));
    }

    /**
     * @param int[] $unitIds
     *
     * @return int[]
     */
    private function getAccessibleUnitIds(array $unitIds) : array
    {
        $res = $unitIds;
        foreach ($unitIds as $uid) {
            $res[] = $this->unitResolver->getOfficialUnitId($uid);
        }

        return array_unique($unitIds);
    }

    /**
     * @param int[] $unitIds
     *
     * @return array<int, MailCredentials[]>
     */
    private function findForUnits(array $unitIds) : array
    {
        $unitIds = $this->getAccessibleUnitIds($unitIds);

        return array_filter($this->credentials->findByUnits($unitIds));
    }
}
