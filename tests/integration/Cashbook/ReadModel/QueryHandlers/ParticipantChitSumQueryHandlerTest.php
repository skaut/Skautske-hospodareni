<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\ReadModel\Queries\ParticipantChitSumQuery;
use App\Model\Utils\MoneyFactory;
use Helpers;
use IntegrationTest;

final class ParticipantChitSumQueryHandlerTest extends IntegrationTest
{
    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Cashbook::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__.'/../../../config/doctrine.neon']);

        parent::_before();

        $cashbook = new Cashbook($this->cashbookId(), CashbookType::get(CashbookType::EVENT));
        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_PARTICIPANT_INCOME_ID, '0.10');
        Helpers::addChitToCashbook($cashbook, null, null, ICategory::CATEGORY_HPD_ID, '0.09');
        Helpers::addChitToCashbook($cashbook, null, null, 999, '2.00');

        $this->entityManager->persist($cashbook);
        $this->entityManager->flush();
    }

    public function testSumsParticipantIncomeChitsExactlyToCents(): void
    {
        $sum = (new ParticipantChitSumQueryHandler($this->entityManager))(
            new ParticipantChitSumQuery($this->cashbookId()),
        );

        self::assertSame('19', $sum->getAmount());
        self::assertTrue(MoneyFactory::fromDecimal('0.19')->equals($sum));
    }

    private function cashbookId(): CashbookId
    {
        return CashbookId::fromString('0233d63a-fd55-416e-b840-0aaac601db13');
    }
}
