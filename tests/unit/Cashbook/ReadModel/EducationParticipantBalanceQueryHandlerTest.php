<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\ReadModel\Queries\EducationParticipantBalanceQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\ParticipantChitSumQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\Event\SkautisEducationId;
use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;
use Mockery as m;

final class EducationParticipantBalanceQueryHandlerTest extends Unit
{
    public function testDifferenceOfParticipantIncomeAndCashbookIsExactToOneCent(): void
    {
        $cashbookId = CashbookId::fromString('cc67c5e5-94c5-4c79-95b0-3333b0c1401d');
        $queryBus = m::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->with(m::type(EducationParticipantIncomeQuery::class))
            ->andReturn(MoneyFactory::fromDecimal('0.30'));
        $queryBus->shouldReceive('handle')
            ->with(m::type(ParticipantChitSumQuery::class))
            ->andReturn(MoneyFactory::fromDecimal('0.49'));

        $balance = (new EducationParticipantBalanceQueryHandler($queryBus))(
            new EducationParticipantBalanceQuery(new SkautisEducationId(123), $cashbookId),
        );

        self::assertSame('-19', $balance->getAmount());
        self::assertSame('-0.19', MoneyFactory::toDecimal($balance));
    }
}
