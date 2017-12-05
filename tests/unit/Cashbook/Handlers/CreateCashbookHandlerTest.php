<?php

namespace Model\Cashbook\Handlers;

use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Commands\Cashbook\CreateCashbook;
use Model\Cashbook\Handlers\Cashbook\CreateCashbookHandler;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Repositories\ICashbookRepository;

class CreateCashbookHandlerTest extends \Codeception\Test\Unit
{

    public function test(): void
    {
        $type = Cashbook\CashbookType::get(Cashbook\CashbookType::CAMP);

        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('save')
            ->once()
            ->with(m::on(function(Cashbook $cashbook) use($type) {
                return $cashbook->getId() === 10 && $cashbook->getType() === $type;
            }));

        $handler = new CreateCashbookHandler($repository);

        $handler->handle(new CreateCashbook(10, $type));

        m::close();
    }

}
