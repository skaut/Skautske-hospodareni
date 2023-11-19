<?php

declare(strict_types=1);

namespace Model\DTO\Travel\Command;

use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Operation;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitItem;

class ChitTest extends Unit
{
    public function testCategories(): void
    {
        $chit = $this->mockChit();
        $this->assertSame('Potraviny, Jízdné, Materiál', $chit->getCategories());
    }

    public function testCategoriesShortcut(): void
    {
        $chit = $this->mockChit();
        $this->assertSame('p, j, m', $chit->getCategoriesShortcut());
    }

    private function mockChit(): Chit
    {
        $items = [
            new ChitItem(new Amount('100'), new Category(1, 'Potraviny', 'p', Operation::EXPENSE(), false), 'chleba, vajíčka'),
            new ChitItem(new Amount('200'), new Category(1, 'Jízdné', 'j', Operation::EXPENSE(), false), 'bus Praha - Brno'),
            new ChitItem(new Amount('300'), new Category(1, 'Materiál', 'm', Operation::EXPENSE(), false), 'kleště'),
        ];

        return new Chit(1, new ChitBody(null, new ChronosDate(), null), false, [], PaymentMethod::CASH(), $items, Operation::EXPENSE(), new Amount('100+200+300'), []);
    }
}
