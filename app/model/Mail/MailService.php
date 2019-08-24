<?php

declare(strict_types=1);

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use Model\DTO\Payment\Mail;
use Model\DTO\Payment\MailFactory;
use Model\Payment\IUnitResolver;
use Model\Payment\MailCredentials;
use Model\Payment\MailCredentialsNotFound;
use Model\Payment\Repositories\IMailCredentialsRepository;
use Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use Model\Unit\Unit;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function assert;

class MailService
{
    /** @var IMailCredentialsRepository */
    private $credentials;

    /** @var IUnitResolver */
    private $unitResolver;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(
        IMailCredentialsRepository $credentials,
        IUnitResolver $unitResolver,
        QueryBus $queryBus
    ) {
        $this->credentials  = $credentials;
        $this->unitResolver = $unitResolver;
        $this->queryBus     = $queryBus;
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
     * @return array<string, array<int, string>>
     */
    public function getCredentialsPairsGroupedByUnit(array $unitIds) : array
    {
        $unitIds     = $this->getAccessibleUnitIds($unitIds);
        $credentials = $this->findForUnits($unitIds);
        $units       = $this->queryBus->handle(new UnitsDetailQuery($unitIds));

        $result = [];
        foreach ($credentials as $unitId => $items) {
            $unit = $units[$unitId];
            assert($unit instanceof Unit);
            foreach ($items as $credential) {
                assert($credential instanceof MailCredentials);
                $result[$unit->getDisplayName()][$credential->getId()] = $credential->getUsername();
            }
        }

        return $result;
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
