<?php

declare(strict_types=1);

namespace Model\Travel;

use Codeception\Test\Unit;
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
}
