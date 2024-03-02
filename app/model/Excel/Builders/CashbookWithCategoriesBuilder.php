<?php

declare(strict_types=1);

namespace Model\Excel\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use function array_map;
use function array_merge;
use function assert;
use function count;
use function explode;
use function mb_strlen;
use function range;
use function uasort;

class CashbookWithCategoriesBuilder
{
    private Worksheet $sheet;

    private const HEADER_ROW              = 2;
    private const SUBHEADER_ROW           = 3;
    private const CATEGORIES_FIRST_COLUMN = 8;

    private const FONT_SIZE = 8;

    // Coefficient for minimal size of column
    private const COLUMN_WIDTH_COEFFICIENT = 1.5;

    public function __construct(private QueryBus $queryBus)
    {
    }

    public function build(Worksheet $sheet, CashbookId $cashbookId, PaymentMethod $paymentMethod): void
    {
        $this->sheet = $sheet;
        $cashbook    = $this->queryBus->handle(new CashbookQuery($cashbookId));
        assert($cashbook instanceof Cashbook);

        $this->addCashbookHeader();

        [$incomeCategories, $expenseCategories] = $this->getCategories($cashbookId);
        $expensesFirstColumn                    = self::CATEGORIES_FIRST_COLUMN + count($incomeCategories);
        $this->addCategoriesHeader(self::CATEGORIES_FIRST_COLUMN, 'Příjmy', $incomeCategories);
        $this->addCategoriesHeader($expensesFirstColumn, 'Výdaje', $expenseCategories);

        $chits      = $this->queryBus->handle(ChitListQuery::withMethod($paymentMethod, $cashbookId));
        $categories = array_merge($incomeCategories, $expenseCategories);

        $this->addChits($chits, $categories, $cashbook->getChitNumberPrefix($paymentMethod) ?? '');
        $this->addSumsRow(count($categories), count($chits));
        $this->addTableStyles(count($chits), count($categories), count($incomeCategories));
    }

    private function addCashbookHeader(): void
    {
        $this->sheet->mergeCells('D2:G2');
        $this->sheet->setCellValue('D2', 'Evidence plateb');

        $columns = ['Dne', 'Dokl.', 'Účel platby', 'Příjem', 'Výdaj', 'Zůstatek'];
        $column  = 2;

        foreach ($columns as $value) {
            $this->sheet->setCellValue([$column++, self::SUBHEADER_ROW], $value);
        }
    }

    private function addSumsRow(int $categoriesCount, int $chitsCount): void
    {
        $firstChitRow = self::SUBHEADER_ROW + 1;
        $resultRow    = $firstChitRow + $chitsCount;

        $categoriesLastColumn = self::CATEGORIES_FIRST_COLUMN + $categoriesCount - 1;

        $columnsWithSum = array_merge(
            range(self::CATEGORIES_FIRST_COLUMN, $categoriesLastColumn), // Categories sum
            [5, 6], // Incomes and expenses sum
        );

        foreach ($columnsWithSum as $column) {
            $this->addColumnSum($column, $resultRow, $firstChitRow);
        }
    }

    /** @param Category[] $categories */
    private function addCategoriesHeader(int $startColumn, string $groupName, array $categories): void
    {
        $lastColumn = $startColumn + count($categories) - 1;
        $this->sheet->mergeCells([$startColumn, self::HEADER_ROW, $lastColumn, self::HEADER_ROW]);
        $this->sheet->setCellValue([$startColumn, self::HEADER_ROW], $groupName);

        foreach ($categories as $index => $category) {
            $column = $startColumn + $index;

            $cell = $this->sheet->getCell([$column, self::SUBHEADER_ROW]);
            $cell->setValue($category->getName());
            $cell->getStyle()
                ->getAlignment()
                ->setWrapText(true);

            $this->guessColumnWidth(
                $this->sheet->getColumnDimensionByColumn($column),
                $category->getName(),
            );
        }
    }

    /**
     * @param Chit[]     $chits
     * @param Category[] $categories
     */
    private function addChits(array $chits, array $categories, string $prefix): void
    {
        $categoryColumns = [];

        foreach ($categories as $index => $category) {
            $categoryColumns[$category->getId()] = self::CATEGORIES_FIRST_COLUMN + $index;
        }

        $row   = self::SUBHEADER_ROW + 1;
        $index = 1;

        $balance = 0;
        foreach ($chits as $chit) {
            $isIncome = $chit->isIncome();
            $amount   = $chit->getAmount()->toFloat();

            $balance += $isIncome ? $amount : -$amount;

            $body            = $chit->getBody();
            $cashbookColumns = [
                $index++,
                $body->getDate()->format('d.m.'),
                $body->getNumber() !== null ? $prefix . $body->getNumber()->toString() : '',
                $chit->getPurpose(),
                $isIncome ? $amount : '',
                ! $isIncome ? $amount : '',
                $balance,
            ];

            foreach ($cashbookColumns as $column => $value) {
                $this->sheet->setCellValue([$column + 1, $row], $value);
            }

            foreach ($chit->getItems() as $item) {
                $column = $categoryColumns[$item->getCategory()->getId()];
                $value  = $this->sheet->getCell([$column, $row])->getValue() + $item->getAmount()->toFloat();
                $this->sheet->setCellValue([$column, $row], $value);
            }

            $row++;
        }
    }

    /** @return Category[][] */
    private function getCategories(CashbookId $cashbookId): array
    {
        $categories = new ArrayCollection(
            $this->queryBus->handle(new CategoryListQuery($cashbookId)),
        );

        $categoriesByOperation = $categories->partition(function (int|string|null $_x = null, Category|null $category = null): bool {
            return $category->getOperationType()->equalsValue(Operation::INCOME);
        });

        // partition keeps original keys
        return array_map(
            fn (Collection $categories): array => $categories->getValues(),
            $categoriesByOperation,
        );
    }

    private function addColumnSum(int $column, int $resultRow, int $firstRow): void
    {
        $lastRow      = $resultRow - 1;
        $resultCell   = $this->sheet->getCell([$column, $resultRow]);
        $stringColumn = $resultCell->getColumn();

        $resultCell->setValue('=SUM(' . $stringColumn . $firstRow . ':' . $stringColumn . $lastRow . ')');
    }

    private function addTableStyles(int $chitsCount, int $categoriesCount, int $incomeCategoriesCount): void
    {
        $sheet = $this->sheet;

        $sheet->mergeCells('A2:C2');

        $lastRow    = self::SUBHEADER_ROW + $chitsCount + 1;
        $lastColumn = self::CATEGORIES_FIRST_COLUMN + $categoriesCount - 1;

        $sheet->getRowDimension(self::SUBHEADER_ROW)->setRowHeight(40);

        $sheet->getStyle('A1:A' . $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimensionByColumn(0)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(1)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(2)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(3)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(4)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(5)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(6)->setAutoSize(true);

        $header = $sheet->getStyle([1, self::HEADER_ROW, $lastColumn, self::HEADER_ROW]);

        $header->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $header->getFont()->setBold(true);

        $wholeTable = $sheet->getStyle([1, self::HEADER_ROW, $lastColumn, $lastRow]);

        // Inner and outer borders
        $wholeTable->getBorders()->applyFromArray([
            'inside' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
            'outline' => [
                'borderStyle' => Border::BORDER_MEDIUM,
            ],
        ]);

        $lastRowStyle = $sheet->getStyle('A' . $lastRow . ':' . Coordinate::stringFromColumnIndex($lastColumn) . $lastRow);
        $lastRowStyle->getFont()->setBold(true);
        $lastRowStyle->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($lastColumn) . $lastRow)
            ->getFont()
            ->setSize(self::FONT_SIZE);

        $separatedColumns = [
            1,
            self::CATEGORIES_FIRST_COLUMN,
            self::CATEGORIES_FIRST_COLUMN + $incomeCategoriesCount,
            4,
        ];

        foreach ($separatedColumns as $column) {
            $sheet->getStyle(Coordinate::stringFromColumnIndex($column) . self::HEADER_ROW . ':' . Coordinate::stringFromColumnIndex($column) . $lastRow)
                ->getBorders()
                ->getLeft()
                ->setBorderStyle(Border::BORDER_MEDIUM);
        }

        $headers = $sheet->getStyle('A' . self::HEADER_ROW . ':' . Coordinate::stringFromColumnIndex($lastColumn) . self::SUBHEADER_ROW);

        $headers->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_MEDIUM);

        $headers->getAlignment()->setHorizontal('center');
    }

    private function guessColumnWidth(ColumnDimension $column, string $content): void
    {
        $words = explode(' ', $content);

        if (count($words) <= 1) {
            $column->setAutoSize(true);

            return;
        }

        uasort($words, function (string $a, string $b) {
            return mb_strlen($a) <=> mb_strlen($b);
        });

        $width = mb_strlen($words[0]) * self::COLUMN_WIDTH_COEFFICIENT;
        $column->setWidth($width);
    }
}
