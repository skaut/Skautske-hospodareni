<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\ICategory;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use function array_map;
use function count;

class ChitListQueryHandlerTest extends \IntegrationTest
{
    private const CASHBOOK_ID = 123;

    protected function getTestedEntites(): array
    {
        return [Cashbook::class, Cashbook\Chit::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/../../../config/doctrine.neon']);
        parent::_before();
    }

    public function testReturnsChitsWithSorting() : void
    {
        $chits = [
            ['2018-01-02', Operation::INCOME, 11],
            ['2018-01-01', Operation::INCOME, 22],
            ['2018-01-01', Operation::EXPENSE, 44],
            ['2018-01-01', Operation::INCOME, 33],
        ];

        $cashbook = new Cashbook($this->getCashbookId(), Cashbook\CashbookType::get(Cashbook\CashbookType::CAMP));

        foreach ($chits as [$date, $operation, $categoryId]) {
            $cashbook->addChit(
                new Cashbook\ChitBody(null, new Date($date), null, Cashbook\Amount::fromFloat(10), ''),
                $this->mockCategory($categoryId, $operation),
                Cashbook\PaymentMethod::get(Cashbook\PaymentMethod::CASH)
            );
        }

        $this->entityManager->persist($cashbook);
        $this->entityManager->flush();

        $handler = new ChitListQueryHandler($this->entityManager, $this->prepareQueryBus());

        $chitDtos   = $handler->handle(new ChitListQuery($this->getCashbookId()));

        $expectedOrder = [2, 4, 3, 1];
        $this->assertCount(count($expectedOrder), $chitDtos);

        foreach ($expectedOrder as $index => $chitId) {
            $dto  = $chitDtos[$index];
            $this->assertSame($chitId, $dto->getId());
        }
    }

    private function prepareQueryBus() : QueryBus
    {
        $bus = m::mock(QueryBus::class);

        $categories = array_map(function (int $id) : Category {
            return m::mock(Category::class, ['getId' => $id]);
        }, [11, 22, 33, 44]);

        $bus->shouldReceive('handle')
            ->withArgs(function (CategoryListQuery $query) {
                return $query->getCashbookId()->equals($this->getCashbookId());
            })->andReturn($categories);

        return $bus;
    }

    private function getCashbookId() : Cashbook\CashbookId
    {
        return Cashbook\CashbookId::fromInt(self::CASHBOOK_ID);
    }

    private function mockCategory(int $id, string $operationType = Operation::INCOME) : ICategory
    {
        return m::mock(ICategory::class, [
            'getId' => $id,
            'getOperationType' => Operation::get($operationType),
        ]);
    }
}
