<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Handlers;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\Commands\Cashbook\CreateCashbook;
use App\Model\Cashbook\Handlers\Cashbook\CreateCashbookHandler;
use App\Model\Cashbook\Repositories\ICashbookRepository;
use Codeception\Test\Unit;
use Mockery as m;

class CreateCashbookHandlerTest extends Unit
{
    public function test(): void
    {
        $type = Cashbook\CashbookType::get(Cashbook\CashbookType::CAMP);
        $cashbookId = Cashbook\CashbookId::generate();

        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('save')
            ->once()
            ->withArgs(static function (Cashbook $cashbook) use ($type, $cashbookId) {
                return $cashbook->getId()->equals($cashbookId)
                    && $cashbook->getType()->equals($type);
            });

        $handler = new CreateCashbookHandler($repository);

        $handler(new CreateCashbook($cashbookId, $type));
    }
}
