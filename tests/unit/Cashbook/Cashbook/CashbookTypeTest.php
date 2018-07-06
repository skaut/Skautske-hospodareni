<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Codeception\Test\Unit;

class CashbookTypeTest extends Unit
{
    public function testTransferCategoriesBetweenAllTestsAreDefined() : void
    {
        foreach (CashbookType::getAvailableValues() as $value) {
            $type = CashbookType::get($value);

            // undefined category would throw exception
            $type->getTransferFromCategoryId();
            $type->getTransferToCategoryId();
        }
    }
}
