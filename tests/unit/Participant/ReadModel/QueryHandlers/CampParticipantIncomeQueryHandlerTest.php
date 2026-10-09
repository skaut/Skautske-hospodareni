<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CampParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Participant;
use App\Model\Event\SkautisCampId;
use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;
use Mockery as m;
use Money\Money;

final class CampParticipantIncomeQueryHandlerTest extends Unit
{
    public function testReturnsExactMoneyForSelectedParticipantCategoryAndPaymentMethod(): void
    {
        $queryBus = m::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->with(m::type(CampParticipantListQuery::class))
            ->andReturn([
                $this->participant('Dítě', 'N', '100.10'),
                $this->participant('Dospělý', 'Y', '200.20'),
                $this->participant('Dospělý', 'N', '300.30'),
            ]);

        $amount = (new CampParticipantIncomeQueryHandler($queryBus))(
            new CampParticipantIncomeQuery(new SkautisCampId(42), true, true),
        );

        self::assertInstanceOf(Money::class, $amount);
        self::assertSame('200.20', MoneyFactory::toDecimal($amount));
    }

    private function participant(string $category, string $onAccount, string $payment): Participant
    {
        return m::mock(Participant::class, [
            'getCategory' => $category,
            'getOnAccount' => $onAccount,
            'getPayment' => MoneyFactory::fromDecimal($payment),
        ]);
    }
}
