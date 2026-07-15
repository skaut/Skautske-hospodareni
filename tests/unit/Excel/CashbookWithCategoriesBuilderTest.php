<?php

declare(strict_types=1);

namespace App\Model\Excel\Builders;

use App\Model\Cashbook\Cashbook\Amount;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Cashbook\ChitBody;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitListQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\DTO\Cashbook\Category;
use App\Model\DTO\Cashbook\Chit;
use App\Model\DTO\Cashbook\ChitItem;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class CashbookWithCategoriesBuilderTest extends Unit
{
    public function testBuildSetsTableStylesWithoutInvalidColumnIndex(): void
    {
        $spreadsheet = new Spreadsheet();
        $builder = new CashbookWithCategoriesBuilder(new CashbookWithCategoriesBuilderQueryBusStub());

        $builder->build($spreadsheet->getActiveSheet(), CashbookId::generate(), PaymentMethod::CASH());

        $sheet = $spreadsheet->getActiveSheet();
        self::assertSame('Evidence plateb', $sheet->getCell('D2')->getValue());
        self::assertTrue($sheet->getColumnDimension('A')->getAutoSize());
        self::assertTrue($sheet->getColumnDimension('G')->getAutoSize());
    }
}

final class CashbookWithCategoriesBuilderQueryBusStub implements QueryBus
{
    public function handle(object $query): mixed
    {
        if ($query instanceof CashbookQuery) {
            return new Cashbook(
                CashbookId::generate(),
                CashbookType::get(CashbookType::EVENT),
                'P',
                null,
                '',
                true,
                true,
            );
        }

        if ($query instanceof CategoryListQuery) {
            return [
                $this->createCategory(1, 'Účastnické poplatky', 'up', Operation::INCOME()),
                $this->createCategory(2, 'Potraviny', 'pot', Operation::EXPENSE()),
            ];
        }

        if ($query instanceof ChitListQuery) {
            $category = $this->createCategory(2, 'Potraviny', 'pot', Operation::EXPENSE());
            $item = new ChitItem(new Amount('120'), $category, 'nákup potravin');

            return [
                new Chit(
                    1,
                    new ChitBody(null, new ChronosDate('2026-07-15'), null),
                    false,
                    [],
                    PaymentMethod::CASH(),
                    [$item],
                    Operation::EXPENSE(),
                    new Amount('120'),
                    [],
                ),
            ];
        }

        return null;
    }

    private function createCategory(int $id, string $name, string $shortcut, Operation $operation): Category
    {
        return new Category($id, $name, $shortcut, $operation, false);
    }
}
