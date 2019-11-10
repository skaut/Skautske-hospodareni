<?php

declare(strict_types=1);

namespace Model\Travel;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Mockery as m;
use Model\DTO\Participant\Participant;
use Model\Participant\PragueParticipants;

class ParticipantTest extends Unit
{
    /**
     * @dataProvider dataSupportability
     */
    public function testSupportability(int $under18, int $totalDays, bool $result) : void
    {
        $pp = new PragueParticipants($under18, 0, 123, 8);
        $this->assertSame($result, $pp->isSupportable($totalDays));
    }

    /**
     * @return mixed[]
     */
    public function dataSupportability() : array
    {
        return [[8, 2, true], [8, 6, true], [8, 7, false], [8, 1, false], [3, 2, false], [3, 8, false]];
    }

    public function testFromListParticipants() : void
    {
        $pp = PragueParticipants::fromParticipantList(new Date('2018-01-01'), [
            m::mock(Participant::class, ['getCity' => 'Praha', 'getBirthday' => new Date('1980-01-01'), 'getDays' => 3]),
            m::mock(Participant::class, ['getCity' => 'Praha', 'getBirthday' => new Date('2080-01-01'), 'getDays' => 3]),
            m::mock(Participant::class, ['getCity' => 'Praha', 'getBirthday' => new Date('1995-01-01'), 'getDays' => 3]),
            m::mock(Participant::class, ['getCity' => 'Praha', 'getBirthday' => new Date('2010-01-01'), 'getDays' => 4]),
            m::mock(Participant::class, ['getCity' => 'Praha', 'getBirthday' => new Date('2010-01-01'), 'getDays' => 4]),
            m::mock(Participant::class, ['getCity' => 'Brno', 'getBirthday' => new Date('2010-01-01'), 'getDays' => 4]),
        ]);
        $this->assertSame(2, $pp->getUnder18());
        $this->assertSame(1, $pp->getBetween18and26());
        $this->assertSame(11, $pp->getPersonDaysUnder26());
        $this->assertSame(5, $pp->getCitizensCount());
    }
}
