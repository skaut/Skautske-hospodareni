<?php

declare(strict_types=1);

namespace Model\Services;

use Codeception\Test\Unit;
use function array_map;

class LanguageTest extends Unit
{
    /**
     * @dataProvider getWithGreaterFirst
     */
    public function testFirstOneIsGreater(string $a, string $b) : void
    {
        $this->assertGreaterThan(0, Language::compare($a, $b));
    }

    /**
     * @dataProvider getWithLowerFirst
     */
    public function testFirstOneIsLower(string $a, string $b) : void
    {
        $this->assertLessThan(0, Language::compare($a, $b));
    }

    /**
     * @dataProvider getWithGreaterFirst
     */
    public function testEqualStrings(string $a) : void
    {
        $this->assertSame(0, Language::compare($a, $a));
    }

    /**
     * @return string[][]
     */
    public function getWithGreaterFirst() : array
    {
        return [
            ['b', 'a'],
            ['ab', 'aa'],
            ['b', 'á'],
            ['č', 'á'],
            ['áb', 'áá'],
            ['ab', 'aá'],
        ];
    }

    /**
     * @return string[][]
     */
    public function getWithLowerFirst() : array
    {
        return array_map('array_reverse', $this->getWithGreaterFirst());
    }
}
