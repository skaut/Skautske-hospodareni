<?php

declare(strict_types=1);

namespace App\Model\DTO\Cashbook;

use App\Model\Cashbook\Cashbook\Amount;
use App\Model\Cashbook\Operation;
use Codeception\Test\Unit;

final class ChitItemTest extends Unit
{
    public function testExpenseProducesNegativeExactAmount(): void
    {
        $item = new ChitItem(
            new Amount('0.19'),
            new Category(1, 'Doprava', 'dop', Operation::EXPENSE(), false),
            'Jízdenka',
        );

        self::assertSame('-19', $item->getSignedAmount()->getAmount());
    }

    public function testIncomeProducesPositiveExactAmount(): void
    {
        $item = new ChitItem(
            new Amount('0.19'),
            new Category(1, 'Příjem', 'pri', Operation::INCOME(), false),
            'Příspěvek',
        );

        self::assertSame('19', $item->getSignedAmount()->getAmount());
    }
}
