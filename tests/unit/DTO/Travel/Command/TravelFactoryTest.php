<?php

namespace Model\DTO\Travel\Command;

use Mockery as m;
use Model\Travel\Command;
use Money\Money;

class TravelFactoryTest extends \Codeception\Test\Unit
{

    public function testCreateListCorrectOrder()
    {
        $firstDay = new \DateTimeImmutable();
        $secondDay = new \DateTimeImmutable();

        $travels = [
            m::mock(Command\TransportTravel::class, [
                'getId' => 3,
                'getDetails' => new Command\TravelDetails($secondDay, 'r', 'Brno', 'Prague'),
                'getPrice' => Money::CZK(10000),
            ]),
            m::mock(Command\TransportTravel::class, [
                'getId' => 1,
                'getDetails' => new Command\TravelDetails($secondDay, 'r', 'Prague', 'Brno'),
                'getPrice' => Money::CZK(10000),
            ]),
            m::mock(Command\TransportTravel::class, [
                'getId' => 2,
                'getDetails' => new Command\TravelDetails($firstDay, 'r', 'Prague', 'Brno'),
                'getPrice' => Money::CZK(10000),
            ]),
        ];

        $command = m::mock(Command::class);
        $command->shouldReceive('getTravels')
            ->once()
            ->andReturn($travels);

        $travels = TravelFactory::createList($command);

        $expectedOrder = [2, 1, 3];
        $actualOrder = array_map(function (Travel $travel) { return $travel->getId(); }, $travels);

        $this->assertSame($expectedOrder, $actualOrder);
    }


}
