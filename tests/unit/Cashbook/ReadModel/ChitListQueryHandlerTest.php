<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\Category;
use Model\Cashbook\Cashbook\Chit;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\Repositories\ICashbookRepository;
use function count;
use function random_bytes;

class ChitListQueryHandlerTest extends Unit
{

    private const CASHBOOK_ID = 123;

    public function testReturnsChitsWithSorting(): void
    {
        $chits = [
            $this->mockChit(1, new Date('2018-01-02'), Operation::INCOME),
            $this->mockChit(2, new Date('2018-01-01'), Operation::INCOME),
            $this->mockChit(3, new Date('2018-01-01'), Operation::EXPENSE),
            $this->mockChit(4, new Date('2018-01-01'), Operation::INCOME),
        ];

        $cashbookId = Cashbook\CashbookId::fromInt(self::CASHBOOK_ID);

        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->with($cashbookId)
            ->andReturn(
                m::mock(Cashbook::class, ['getChits' => $chits])
            );

        $handler = new ChitListQueryHandler($repository);

        $chitDtos = $handler->handle(new ChitListQuery($cashbookId));

        $expectedOrder = [2, 4, 3, 1];
        $this->assertCount(count($expectedOrder), $chitDtos);

        foreach ($expectedOrder as $index => $chitId) {
            $dto = $chitDtos[$index];
            $chit = $chits[$chitId - 1];

            $this->assertSame($chit->getId(), $dto->getId());
            $this->assertSame($chit->getDate(), $dto->getDate());
            $this->assertSame($chit->getCategory(), $dto->getCategory());
            $this->assertSame($chit->getNumber(), $dto->getNumber());
            $this->assertSame($chit->getAmount(), $dto->getAmount());
            $this->assertSame($chit->getPurpose(), $dto->getPurpose());
            $this->assertSame($chit->getRecipient(), $dto->getRecipient());
            $this->assertSame($chit->isLocked(), $dto->isLocked());
        }
    }

    private function mockChit(int $id, Date $date, string $operation): Chit
    {
        return m::mock(Chit::class, [
            'getId'         => $id,
            'getDate'       => $date,
            'getCategory'   => new Category(1, Operation::get($operation)),
            'getNumber'     => new Cashbook\ChitNumber('132'),
            'getAmount'     => new Cashbook\Amount('1'),
            'getPurpose'    => random_bytes(100),
            'getRecipient'  => new Cashbook\Recipient('František Maša'),
            'isLocked'      => TRUE,
        ]);
    }

}
