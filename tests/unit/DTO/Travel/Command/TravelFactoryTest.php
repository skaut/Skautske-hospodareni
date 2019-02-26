<?php

declare(strict_types=1);

namespace Model\DTO\Travel\Command;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Mockery as m;
use Model\Travel\Command;
use Money\Money;
use function array_map;

class TravelFactoryTest extends Unit
{
    public function testCreateListCorrectOrder() : void
    {
        $firstDay      = new Date('2018-01-01');
        $secondDay     = new Date('2018-01-02');
        $transportType = 'r';

        $travels = [
            m::mock(Command\TransportTravel::class, [
                'getId' => 3,
                'getDetails' => new Command\TravelDetails($secondDay, $transportType, 'Brno', 'Prague'),
                'getPrice' => Money::CZK(10000),
            ]),
            m::mock(Command\TransportTravel::class, [
                'getId' => 1,
                'getDetails' => new Command\TravelDetails($secondDay, $transportType, 'Prague', 'Brno'),
                'getPrice' => Money::CZK(10000),
            ]),
            m::mock(Command\TransportTravel::class, [
                'getId' => 2,
                'getDetails' => new Command\TravelDetails($firstDay, $transportType, 'Prague', 'Brno'),
                'getPrice' => Money::CZK(10000),
            ]),
        ];

        $command = m::mock(Command::class);
        $command->shouldReceive('getTravels')
            ->once()
            ->andReturn($travels);

        $travels = TravelFactory::createList($command);

        $expectedOrder = [2, 1, 3];
        $actualOrder   = array_map(function (Travel $travel) {
            return $travel->getId();
        }, $travels);

        $this->assertSame($expectedOrder, $actualOrder);
    }
}
