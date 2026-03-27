<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use Codeception\Test\Unit;
use Mockery as m;

final class CashbookQueryHandlerTest extends Unit
{
    private const CASHBOOK_ID = '7f0ce65f-c823-4a83-8571-a0a851abff11';

    public function testMapsCashbookToDto(): void
    {
        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->withArgs(fn (CashbookId $id): bool => $id->equals($this->cashbookId()))
            ->andReturn(m::mock(Cashbook::class, [
                'getId' => $this->cashbookId(),
                'getType' => CashbookType::get(CashbookType::EVENT),
                'getCashChitNumberPrefix' => 'P',
                'getBankChitNumberPrefix' => 'B',
                'getNote' => 'note',
                'hasOnlyNumericChitNumbers' => true,
            ]));

        $dto = (new CashbookQueryHandler($repository))(new CashbookQuery($this->cashbookId()));

        self::assertSame(self::CASHBOOK_ID, $dto->getId());
        self::assertSame('P', $dto->getChitNumberPrefix(PaymentMethod::CASH()));
        self::assertSame('B', $dto->getChitNumberPrefix(PaymentMethod::BANK()));
        self::assertSame('note', $dto->getNote());
        self::assertTrue($dto->hasOnlyNumericChitNumbers(PaymentMethod::CASH()));
        self::assertTrue($dto->hasOnlyNumericChitNumbers(PaymentMethod::BANK()));
    }

    private function cashbookId(): CashbookId
    {
        return CashbookId::fromString(self::CASHBOOK_ID);
    }
}
