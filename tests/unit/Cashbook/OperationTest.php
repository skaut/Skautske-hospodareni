<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Codeception\Test\Unit;

final class OperationTest extends Unit
{
    /**
     * @dataProvider getInverseTypes
     */
    public function testInverse(string $originalType, string $inverseType) : void
    {
        $this->assertTrue(Operation::get($originalType)->getInverseOperation()->equalsValue($inverseType));
    }

    /**
     * @return string[][]
     */
    public function getInverseTypes() : array
    {
        return [
            [Operation::INCOME, Operation::EXPENSE],
            [Operation::EXPENSE, Operation::INCOME],
        ];
    }
}
