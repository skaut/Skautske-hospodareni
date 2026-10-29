<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\CampParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Participant\Participant;
use App\Model\Event\SkautisCampId;
use App\Model\Participant\ZeroParticipantIncome;
use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;
use Mockery as m;
use Money\Money;

use function array_map;

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

    /** @return array<string, array{list<string>}> */
    public function provideNonPositivePayments(): array
    {
        return [
            'nulové platby' => [['0.00', '0.00']],
            'záporný součet plateb' => [['100.00', '-250.00']],
        ];
    }

    /**
     * Import HPD z výsledku vytváří doklad, který nulovou ani zápornou částku nepřijme.
     *
     * @dataProvider provideNonPositivePayments
     *
     * @param list<string> $payments
     */
    public function testThrowsWhenSelectedParticipantsHaveNoPositiveIncome(array $payments): void
    {
        $queryBus = m::mock(QueryBus::class);
        $queryBus->shouldReceive('handle')
            ->once()
            ->with(m::type(CampParticipantListQuery::class))
            ->andReturn(array_map(fn (string $payment) => $this->participant('Dítě', 'N', $payment), $payments));

        $this->expectException(ZeroParticipantIncome::class);

        (new CampParticipantIncomeQueryHandler($queryBus))(
            new CampParticipantIncomeQuery(new SkautisCampId(42), false, false),
        );
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
