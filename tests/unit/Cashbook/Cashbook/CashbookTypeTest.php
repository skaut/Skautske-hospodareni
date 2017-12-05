<?php

namespace Model\Cashbook\Cashbook;

class CashbookTypeTest extends \Codeception\Test\Unit
{

    public function testTransferCategoriesBetweenAllTestsAreDefined(): void
    {
        foreach(CashbookType::getAvailableValues() as $value) {
            $type = CashbookType::get($value);

            // undefined category would throw exception
            $type->getTransferFromCategoryId();
            $type->getTransferToCategoryId();
        }
    }

}
