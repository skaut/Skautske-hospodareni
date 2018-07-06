<?php

declare(strict_types=1);

namespace Model\Cashbook\Handlers;

use Codeception\Test\Unit;
use Mockery as m;
use Model\Cashbook\Cashbook;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook as Commands;
use Model\Cashbook\Handlers\Cashbook\ClearCashbookHandler;
use Model\Cashbook\Handlers\Cashbook\LockCashbookHandler;
use Model\Cashbook\Handlers\Cashbook\LockChitHandler;
use Model\Cashbook\Handlers\Cashbook\RemoveChitFromCashbookHandler;
use Model\Cashbook\Handlers\Cashbook\UnlockChitHandler;
use Model\Cashbook\Handlers\Cashbook\UpdateChitNumberPrefixHandler;
use Model\Cashbook\Repositories\ICashbookRepository;

final class CashbookHandlersTest extends Unit
{
    /**
     * @dataProvider dataHandlers
     */
    public function test(
        $commandInstance,
        string $handlerClass,
        string $expectedMethodCall,
        array $expectedMethodArguments
    ) : void {
        $cashbook = m::mock(Cashbook::class);
        $cashbook->shouldReceive($expectedMethodCall)
            ->once()
            ->withArgs($expectedMethodArguments);

        $repository = m::mock(ICashbookRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->withArgs(function (CashbookId $id) {
                return $id->equals($this->getCashbookId());
            })
            ->andReturn($cashbook);

        $repository->shouldReceive('save')
            ->once()
            ->with($cashbook);

        $handler = new $handlerClass($repository);

        $handler->handle($commandInstance);
    }

    public function dataHandlers() : array
    {
        return [
            [
                new Commands\LockCashbook($this->getCashbookId(), 15),
                LockCashbookHandler::class,
                'lock',
                [15],
            ],
            [
                new Commands\ClearCashbook($this->getCashbookId()),
                ClearCashbookHandler::class,
                'clear',
                [],
            ],
            [
                new Commands\LockChit($this->getCashbookId(), 15, 16),
                LockChitHandler::class,
                'lockChit',
                [15, 16],
            ],
            [
                new Commands\RemoveChitFromCashbook($this->getCashbookId(), 150),
                RemoveChitFromCashbookHandler::class,
                'removeChit',
                [150],
            ],
            [
                new Commands\UnlockChit($this->getCashbookId(), 155),
                UnlockChitHandler::class,
                'unlockChit',
                [155],
            ],
            [
                new Commands\UpdateChitNumberPrefix($this->getCashbookId(), 'V123'),
                UpdateChitNumberPrefixHandler::class,
                'updateChitNumberPrefix',
                ['V123'],
            ],
        ];
    }

    private function getCashbookId() : Cashbook\CashbookId
    {
        return Cashbook\CashbookId::fromString('132');
    }
}
