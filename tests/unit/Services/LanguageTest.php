<?php

namespace Model\Services;

class LanguageTest extends \Codeception\Test\Unit
{

    /**
     * @dataProvider getWithGreaterFirst
     */
    public function testFirstOneIsGreater(string $a, string $b): void
    {
        $this->assertGreaterThan(0, Language::compare($a, $b));
    }

    /**
     * @dataProvider getWithLowerFirst
     */
    public function testFirstOneIsLower(string $a, string $b): void
    {
        $this->assertLessThan(0, Language::compare($a, $b));
    }

    /**
     * @dataProvider getWithGreaterFirst
     */
    public function testEqualStrings(string $a)
    {
        $this->assertSame(0, Language::compare($a, $a));
    }

    public function getWithGreaterFirst(): array
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

    public function getWithLowerFirst(): array
    {
        return array_map('array_reverse', $this->getWithGreaterFirst());
    }

}
