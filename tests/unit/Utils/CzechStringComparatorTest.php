<?php

declare(strict_types=1);

namespace App\Utils;

use Codeception\Test\Unit;

use function usort;

final class CzechStringComparatorTest extends Unit
{
    public function testSortsNamesByCzechAlphabet(): void
    {
        $names = ['David', 'Čeněk', 'Ciryl', 'Bořek', 'Adam'];

        usort($names, [CzechStringComparator::class, 'compare']);

        $this->assertSame(['Adam', 'Bořek', 'Ciryl', 'Čeněk', 'David'], $names);
    }
}
