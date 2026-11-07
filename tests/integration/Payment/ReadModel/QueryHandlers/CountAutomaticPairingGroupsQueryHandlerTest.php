<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Payment\Group;
use App\Model\Payment\ReadModel\Queries\CountAutomaticPairingGroupsQuery;
use BankingFixtures;
use IntegrationTest;

final class CountAutomaticPairingGroupsQueryHandlerTest extends IntegrationTest
{
    use BankingFixtures;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [BankAccount::class, Group::class];
    }

    public function testCountsOnlyOpenGroupsPairedByCron(): void
    {
        $fio = $this->createBankAccountFixture('FIO');
        $gpc = $this->createBankAccountFixture('GPC', null, BankTransactionSource::GPC);
        $this->entityManager->persist($fio);
        $this->entityManager->persist($gpc);
        $this->entityManager->flush();

        $this->haveGroup($fio, automaticPairing: true, open: true);
        $this->haveGroup($fio, automaticPairing: true, open: true);
        $this->haveGroup($fio, automaticPairing: true, open: false);
        $this->haveGroup($fio, automaticPairing: false, open: true);
        $this->haveGroup($gpc, automaticPairing: true, open: true);
        $this->haveGroup(null, automaticPairing: true, open: true);

        self::assertSame(2, (new CountAutomaticPairingGroupsQueryHandler($this->entityManager))(new CountAutomaticPairingGroupsQuery()));
    }

    private function haveGroup(?BankAccount $bankAccount, bool $automaticPairing, bool $open): void
    {
        $group = $this->createPaymentGroupFixture($bankAccount);
        $group->setAutomaticPairingEnabled($automaticPairing);

        if (! $open) {
            $group->close('Uzavřeno v testu');
        }

        $this->entityManager->persist($group);
        $this->entityManager->flush();
    }
}
