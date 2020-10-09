<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers\MailCredentials;

use Model\DTO\Payment\Mail;
use Model\DTO\Payment\MailFactory;
use Model\Payment\IUnitResolver;
use Model\Payment\MailCredentials;
use Model\Payment\ReadModel\Queries\MailCredentials\MailCredentialsAccessibleByGroupsQuery;
use Model\Payment\Repositories\IMailCredentialsRepository;
use Model\Payment\Services\IMailCredentialsAccessChecker;
use function array_map;
use function array_merge;
use function array_unique;
use function assert;

final class MailCredentialsAccessibleByGroupsQueryHandler
{
    private IMailCredentialsAccessChecker $accessChecker;

    private IMailCredentialsRepository $mailCredentials;

    private IUnitResolver $unitResolver;

    public function __construct(
        IMailCredentialsAccessChecker $accessChecker,
        IMailCredentialsRepository $mailCredentials,
        IUnitResolver $unitResolver
    ) {
        $this->accessChecker   = $accessChecker;
        $this->mailCredentials = $mailCredentials;
        $this->unitResolver    = $unitResolver;
    }

    /**
     * @return Mail[]
     */
    public function __invoke(MailCredentialsAccessibleByGroupsQuery $query) : array
    {
        $unitIds            = $query->getUnitIds();
        $allMailCredentials = array_merge(
            [],
            ...$this->mailCredentials->findByUnits($this->unitsOwningMailCredentials($unitIds))
        );

        $accessibleMailCredentials = [];

        foreach ($allMailCredentials as $mailCredentials) {
            assert($mailCredentials instanceof MailCredentials);

            if (! $this->accessChecker->allUnitsHaveAccessToMailCredentials($unitIds, $mailCredentials->getId())) {
                continue;
            }

            $accessibleMailCredentials[] = MailFactory::create($mailCredentials);
        }

        return $accessibleMailCredentials;
    }

    /**
     * @param int[] $unitIds
     *
     * @return int[]
     */
    private function unitsOwningMailCredentials(array $unitIds) : array
    {
        return array_unique(
            array_merge([], $unitIds, array_map([$this->unitResolver, 'getOfficialUnitId'], $unitIds))
        );
    }
}
