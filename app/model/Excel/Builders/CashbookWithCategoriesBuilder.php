<?php

declare(strict_types=1);

namespace Model\Excel\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Category;
use Model\DTO\Cashbook\Chit;
use PHPExcel_Exception;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function explode;
use function mb_strlen;
use function range;
use function uasort;

class CashbookWithCategoriesBuilder
{
    /** @var Worksheet */
    private $sheet;

    /** @var QueryBus */
    private $queryBus;

    private const HEADER_ROW              = 2;
    private const SUBHEADER_ROW           = 3;
    private const CATEGORIES_FIRST_COLUMN = 8;

    private const FONT_SIZE = 8;

    // Coefficient for minimal size of column
    private const COLUMN_WIDTH_COEFFICIENT = 1.5;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    public function build(Worksheet $sheet, CashbookId $cashbookId, PaymentMethod $paymentMethod) : void
    {
        $this->sheet = $sheet;

        $this->addCashbookHeader();

        [$incomeCategories, $expenseCategories] = $this->getCategories($cashbookId);
        $expensesFirstColumn                    = self::CATEGORIES_FIRST_COLUMN + count($incomeCategories);
        $this->addCategoriesHeader(self::CATEGORIES_FIRST_COLUMN, 'Příjmy', $incomeCategories);
        $this->addCategoriesHeader($expensesFirstColumn, 'Výdaje', $expenseCategories);

        $chits      = $this->queryBus->handle(ChitListQuery::withMethod($paymentMethod, $cashbookId));
        $categories = array_merge($incomeCategories, $expenseCategories);

        $this->addChits($chits, $categories);
        $this->addSumsRow(count($categories), count($chits));
        $this->addTableStyles(count($chits), count($categories), count($incomeCategories));
    }

    private function addCashbookHeader() : void
    {
        $this->sheet->mergeCells('D2:G2');
        $this->sheet->setCellValue('D2', 'Evidence plateb');

        $columns = ['Dne', 'Dokl.', 'Účel platby', 'Příjem', 'Výdaj', 'Zůstatek'];
        $column  = 2;

        foreach ($columns as $value) {
            $this->sheet->setCellValueByColumnAndRow(
                $column++,
                self::SUBHEADER_ROW,
                $value
            );
        }
    }

    private function addSumsRow(int $categoriesCount, int $chitsCount) : void
    {
        $firstChitRow = self::SUBHEADER_ROW + 1;
        $resultRow    = $firstChitRow + $chitsCount;

        $categoriesLastColumn = self::CATEGORIES_FIRST_COLUMN + $categoriesCount - 1;

        $columnsWithSum = array_merge(
            range(self::CATEGORIES_FIRST_COLUMN, $categoriesLastColumn), // Categories sum
            [5, 6, 7] // Incomes and expenses sum
        );

        foreach ($columnsWithSum as $column) {
            $this->addColumnSum($column, $resultRow, $firstChitRow);
        }
    }

    /**
     * @param Category[] $categories
     *
     * @throws PHPExcel_Exception
     */
    private function addCategoriesHeader(int $startColumn, string $groupName, array $categories) : void
    {
        $lastColumn = $startColumn + count($categories) - 1;
        $this->sheet->mergeCellsByColumnAndRow($startColumn, self::HEADER_ROW, $lastColumn, self::HEADER_ROW);
        $this->sheet->setCellValueByColumnAndRow($startColumn, self::HEADER_ROW, $groupName);

        foreach ($categories as $index => $category) {
            $column = $startColumn + $index;

            $cell = $this->sheet->getCellByColumnAndRow($column, self::SUBHEADER_ROW);
            $cell->setValue($category->getName());
            $cell->getStyle()
                ->getAlignment()
                ->setWrapText(true);

            $this->guessColumnWidth(
                $this->sheet->getColumnDimensionByColumn($column),
                $category->getName()
            );
        }
    }

    /**
     * @param Chit[]     $chits
     * @param Category[] $categories
     */
    private function addChits(array $chits, array $categories) : void
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

            $cashbookColumns = [
                $index++,
                $chit->getDate()->format('d.m.'),
                $chit->getNumber() !== null ? $chit->getNumber()->toString() : '',
                $chit->getPurpose(),
                $isIncome ? $amount : '',
                ! $isIncome ? $amount : '',
                $balance,
            ];

            foreach ($cashbookColumns as $column => $value) {
                $this->sheet->setCellValueByColumnAndRow($column + 1, $row, $value);
            }

            foreach ($chit->getItems() as $item) {
                $column = $categoryColumns[$item->getCategory()->getId()];
                $value  = $this->sheet->getCellByColumnAndRow($column, $row)->getValue() + $item->getAmount()->toFloat();
                $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
            }
            $row++;
        }
    }

    /**
     * @return Category[][]
     */
    private function getCategories(CashbookId $cashbookId) : array
    {
        $categories = new ArrayCollection(
            $this->queryBus->handle(new CategoryListQuery($cashbookId))
        );

        $categoriesByOperation = $categories->partition(function ($_, Category $category) : bool {
            return $category->getOperationType()->equalsValue(Operation::INCOME);
        });

        return array_map(function (ArrayCollection $categories) : array {
            return array_values($categories->toArray()); // partition keeps original keys
        }, $categoriesByOperation);
    }

    private function addColumnSum(int $column, int $resultRow, int $firstRow) : void
    {
        $lastRow      = $resultRow - 1;
        $resultCell   = $this->sheet->getCellByColumnAndRow($column, $resultRow);
        $stringColumn = $resultCell->getColumn();

        $resultCell->setValue('=SUM(' . $stringColumn . $firstRow . ':' . $stringColumn . $lastRow . ')');
    }

    private function addTableStyles(int $chitsCount, int $categoriesCount, int $incomeCategoriesCount) : void
    {
        $sheet = $this->sheet;

        $sheet->mergeCells('A2:C2');

        $lastRow    = self::SUBHEADER_ROW + $chitsCount + 1;
        $lastColumn = self::CATEGORIES_FIRST_COLUMN + $categoriesCount - 1;

        $sheet->getRowDimension(self::SUBHEADER_ROW)->setRowHeight(40);

        $sheet->getStyleByColumnAndRow(0, 0, 0, $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimensionByColumn(0)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(1)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(2)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(3)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(4)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(5)->setAutoSize(true);
        $sheet->getColumnDimensionByColumn(6)->setAutoSize(true);

        $header = $sheet->getStyleByColumnAndRow(1, self::HEADER_ROW, $lastColumn, self::HEADER_ROW);

        $header->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $header->getFont()->setBold(true);

        $wholeTable = $sheet->getStyleByColumnAndRow(1, self::HEADER_ROW, $lastColumn, $lastRow);

        // Inner and outer borders
        $wholeTable->getBorders()->applyFromArray([
            'inside' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
            'outline' => [
                'borderStyle' => Border::BORDER_MEDIUM,
            ],
        ]);

        $lastRowStyle = $sheet->getStyleByColumnAndRow(0, $lastRow, $lastColumn, $lastRow);
        $lastRowStyle->getFont()->setBold(true);
        $lastRowStyle->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->getStyleByColumnAndRow(0, 1, $lastColumn, $lastRow)
            ->getFont()
            ->setSize(self::FONT_SIZE);

        $separatedColumns = [
            1,
            self::CATEGORIES_FIRST_COLUMN,
            self::CATEGORIES_FIRST_COLUMN + $incomeCategoriesCount,
            4,
        ];

        foreach ($separatedColumns as $column) {
            $sheet->getStyleByColumnAndRow($column, self::HEADER_ROW, $column, $lastRow)
                ->getBorders()
                ->getLeft()
                ->setBorderStyle(Border::BORDER_MEDIUM);
        }

        $headers = $sheet->getStyleByColumnAndRow(0, self::HEADER_ROW, $lastColumn, self::SUBHEADER_ROW);

        $headers->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_MEDIUM);

        $headers->getAlignment()->setHorizontal('center');
    }

    private function guessColumnWidth(ColumnDimension $column, string $content) : void
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
