<?php

declare(strict_types=1);

namespace Model\DTO\Travel\Command;

use Cake\Chronos\Date;
use Codeception\Test\Unit;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Operation;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitItem;
use Money\Money;

class ChitTest extends Unit
{
    public function testCategories() : void
    {
        $chit = $this->mockChit();
        $this->assertSame('Potraviny, Jízdné, Materiál', $chit->getCategories());
    }

    public function testCategoriesShortcut() : void
    {
        $chit = $this->mockChit();
        $this->assertSame('p, j, m', $chit->getCategoriesShortcut());
    }

    private function mockChit() : Chit
    {
        $items = [
            new ChitItem(new Amount('100'), new Category(1, 'Potraviny', Money::CZK(100), 'p', Operation::EXPENSE(), false), 'chleba, vajíčka'),
            new ChitItem(new Amount('200'), new Category(1, 'Jízdné', Money::CZK(100), 'j', Operation::EXPENSE(), false), 'bus Praha - Brno'),
            new ChitItem(new Amount('300'), new Category(1, 'Materiál', Money::CZK(100), 'm', Operation::EXPENSE(), false), 'kleště'),
        ];

        return new Chit(1, new ChitBody(null, new Date(), null), false, [], PaymentMethod::CASH(), $items, Operation::EXPENSE(), new Amount('100+200+300'), []);
    }
}
