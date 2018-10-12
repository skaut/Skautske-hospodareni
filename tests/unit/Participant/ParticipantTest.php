<?php

declare(strict_types=1);

namespace Model\Travel;

use Mockery as m;
use Model\Participant\PragueParticipants;
use Model\Travel\Vehicle\Metadata;
use Model\Unit\Unit;

class ParticipantTest extends \Codeception\Test\Unit
{
    /**
     * @dataProvider dataSupportability
     */
    public function testSupportability(int $under18, int $totalDays, bool $result) : void
    {
        $pp = new PragueParticipants($under18, 0, 123, 8);
        $this->assertSame ($result, $pp->isSupportable ($totalDays));
    }

    public function dataSupportability() : array
    {
        return [[8, 2, true], [8, 6, true], [8, 7, false], [8, 1, false], [3, 2, false], [3, 8, false]];
    }

}
