<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Payment\BankAccount\BankAccountId;
use App\Model\Payment\Group;
use App\Model\Payment\ReadModel\Queries\CountGroupsWithBankAccountQuery;
use IntegrationTest;

final class CountGroupsWithBankAccountQueryHandlerTest extends IntegrationTest
{
    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Group::class];
    }

    public function test(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $this->tester->haveInDatabase('pa_group', [
                'name' => 'Test',
                'bank_account_id' => 10,
                'last_pairing' => '2018-10-01 15:30:00',
                'state' => Group::STATE_CLOSED,
                'note' => '',
            ]);
        }

        $query = new CountGroupsWithBankAccountQuery(new BankAccountId(10));
        $this->assertSame(3, (new CountGroupsWithBankAccountQueryHandler($this->entityManager))($query));
    }
}
