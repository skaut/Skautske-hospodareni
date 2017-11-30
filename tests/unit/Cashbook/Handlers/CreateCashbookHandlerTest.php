<?php

namespace Model\Cashbook\Handlers;

use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\Handlers\Cashbook\CreateCashbookHandler;
use Model\Cashbook\Repositories\ICashbookRepository;

class CreateCashbookHandlerTest extends \Codeception\Test\Unit
{

    public function test(): void
    {
        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('save')
            ->once()
            ->with(m::on(function(Cashbook $cashbook) {
                return $cashbook->getId() === 10;
            }));

        $handler = new CreateCashbookHandler($repository);

        $handler->handle(new CreateCashbook(10));

        m::close();
    }

}
