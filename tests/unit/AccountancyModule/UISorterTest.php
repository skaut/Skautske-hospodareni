<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use Codeception\Test\Unit;

final class UISorterTest extends Unit
{
    /** @var UISorter */
    private $sorter;

    protected function _before() : void
    {
        $this->sorter = new UISorter('foo');
    }

    public function testSorterForStrings() : void
    {
        $this->assertSame(-1, ($this->sorter)($this->createObject('Č'), $this->createObject('D')));
    }

    public function testSorterForNumbers() : void
    {
        $this->assertSame(1, ($this->sorter)($this->createObject(10), $this->createObject(1)));
    }

    /**
     * @param mixed $value
     */
    private function createObject($value) : object
    {
        return new class($value) {
            /** @var mixed */
            private $foo;

            /**
             * @param mixed $foo
             */
            public function __construct($foo)
            {
                $this->foo = $foo;
            }

            /**
             * @return mixed
             */
            public function getFoo()
            {
                return $this->foo;
            }
        };
    }
}
