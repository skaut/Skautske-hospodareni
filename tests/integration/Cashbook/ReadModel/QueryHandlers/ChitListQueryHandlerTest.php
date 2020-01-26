<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Helpers;
use IntegrationTest;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Category;
use function count;

class ChitListQueryHandlerTest extends IntegrationTest
{
    private const CASHBOOK_ID = '123';

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots() : array
    {
        return [Cashbook::class];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles([__DIR__ . '/../../../config/doctrine.neon']);

        parent::_before();

        $chits = [
            ['2018-01-02', Operation::INCOME, 11, PaymentMethod::CASH],
            ['2018-01-01', Operation::INCOME, 22, PaymentMethod::BANK],
            ['2018-01-01', Operation::EXPENSE, 44, PaymentMethod::CASH],
            ['2018-01-01', Operation::INCOME, 33, PaymentMethod::BANK],
        ];

        $cashbook = new Cashbook($this->getCashbookId(), Cashbook\CashbookType::get(Cashbook\CashbookType::CAMP));

        foreach ($chits as [$date, $operation, $categoryId, $paymentMethod]) {
            $body     = new Cashbook\ChitBody(null, new Date($date), null);
            $category = Helpers::mockChitItemCategory($categoryId, Operation::get($operation));
            $cashbook->addChit(
                $body,
                PaymentMethod::get($paymentMethod),
                [new Cashbook\ChitItem(Cashbook\Amount::fromFloat(10), $category, '')],
                Helpers::mockCashbookCategories($categoryId)
            );
        }

        $this->entityManager->persist($cashbook);
        $this->entityManager->flush();
    }

    public function testReturnsSortedChitsWithoutSpecifiedPaymentMethod() : void
    {
        $handler = new ChitListQueryHandler($this->entityManager, $this->prepareQueryBus());

        $chits = $handler(ChitListQuery::all($this->getCashbookId()));

        $expectedOrder = [2, 4, 3, 1];
        $this->assertCount(count($expectedOrder), $chits);

        foreach ($expectedOrder as $index => $chitId) {
            $dto = $chits[$index];
            $this->assertSame($chitId, $dto->getId());
        }
    }

    public function testReturnsSortedChitsOfSpecifiedPaymentMethod() : void
    {
        $handler = new ChitListQueryHandler($this->entityManager, $this->prepareQueryBus());

        $chits = $handler(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->getCashbookId()));

        $expectedOrder = [3, 1];
        $this->assertCount(count($expectedOrder), $chits);

        foreach ($expectedOrder as $index => $chitId) {
            $dto = $chits[$index];
            $this->assertSame($chitId, $dto->getId());
        }
    }

    public function testReturnsEmptyListIfCashbookDoesNotExist() : void
    {
        $this->assertSame(
            [],
            (new ChitListQueryHandler($this->entityManager, $this->prepareQueryBus()))(
                ChitListQuery::all(Cashbook\CashbookId::generate())
            )
        );
    }

    private function prepareQueryBus() : QueryBus
    {
        $bus = m::mock(QueryBus::class);

        $ids        = [11, 22, 33, 44];
        $categories = [];
        foreach ($ids as $id) {
            $categories[$id] =m::mock(Category::class, ['getId' => $id]);
        }

        $bus->shouldReceive('handle')
            ->withArgs(function (CategoryListQuery $query) {
                return $query->getCashbookId()->equals($this->getCashbookId());
            })->andReturn($categories);

        return $bus;
    }

    private function getCashbookId() : Cashbook\CashbookId
    {
        return Cashbook\CashbookId::fromString(self::CASHBOOK_ID);
    }
}
