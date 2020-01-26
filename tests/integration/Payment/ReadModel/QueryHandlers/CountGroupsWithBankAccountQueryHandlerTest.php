<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use IntegrationTest;
use Model\Payment\BankAccount\BankAccountId;
use Model\Payment\Group;
use Model\Payment\ReadModel\Queries\CountGroupsWithBankAccountQuery;

final class CountGroupsWithBankAccountQueryHandlerTest extends IntegrationTest
{
    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots() : array
    {
        return [Group::class];
    }

    public function test() : void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->tester->haveInDatabase('pa_group', [
                'label' => 'Test',
                'bank_account_id' => 10,
                'last_pairing' => '2018-10-01 15:30:00',
                'state' => Group::STATE_CLOSED,
                'state_info' => '',
            ]);
        }

        $query = new CountGroupsWithBankAccountQuery(new BankAccountId(10));
        $this->assertSame(3, (new CountGroupsWithBankAccountQueryHandler($this->entityManager))($query));
    }
}
