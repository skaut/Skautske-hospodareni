<?php

namespace Model\Excel\Builders;

use Dibi\Row;
use Model\Cashbook\Category;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Repositories\IStaticCategoryRepository;
use Model\EventEntity;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashbookWithCategoriesBuilder
{

    /** @var IStaticCategoryRepository */
    private $categories;

    /** @var Worksheet */
    private $sheet;

    private const HEADER_ROW = 2;
    private const SUBHEADER_ROW = 3;
    private const CATEGORIES_FIRST_COLUMN = 7;

    private const FONT_SIZE = 8;

    // Coefficient for minimal size of column
    private const COLUMN_WIDTH_COEFFICIENT = 1.1;

    public function __construct(IStaticCategoryRepository $categories)
    {
        $this->categories = $categories;
    }

    public function build(Worksheet $sheet, EventEntity $eventEntity, int $eventId, ObjectType $type): void
    {
        $this->sheet = $sheet;

        $this->addCashbookHeader();

        [$incomeCategories, $expenseCategories] = $this->getCategories($type);
        $expensesFirstColumn = self::CATEGORIES_FIRST_COLUMN + count($incomeCategories);
        $this->addCategoriesHeader(self::CATEGORIES_FIRST_COLUMN, 'Příjmy', $incomeCategories);
        $this->addCategoriesHeader($expensesFirstColumn, 'Výdaje', $expenseCategories);

        $chits = $eventEntity->chits->getAll($eventId);
        $categories = array_merge($incomeCategories, $expenseCategories);

        $this->addChits($chits, $categories);
        $this->addSumsRow(count($categories), count($chits));
        $this->addTableStyles(count($chits), count($categories), count($incomeCategories));
    }

    private function addCashbookHeader(): void
    {
        $this->sheet->mergeCells('E2:G2');
        $this->sheet->setCellValue('E2', 'Pokladní kniha');

        $columns = ['Dne', 'Dokl.', 'Účel platby', 'Příjem', 'Výdaj', 'Zůstatek'];
        $column = 1;

        foreach($columns as $value) {
            $this->sheet->setCellValueByColumnAndRow(
                $column++,
                self::SUBHEADER_ROW,
                $value
            );
        }
    }


    private function addSumsRow(int $categoriesCount, int $chitsCount): void
    {
        $firstChitRow = self::SUBHEADER_ROW + 1;
        $resultRow = self::SUBHEADER_ROW + $chitsCount;

        $categoriesLastColumn = self::CATEGORIES_FIRST_COLUMN + $categoriesCount;

        $columnsWithSum = array_merge(
            range(self::CATEGORIES_FIRST_COLUMN, $categoriesLastColumn), // Categories sum
            [4, 5] // Incomes and expenses sum
        );

        foreach ($columnsWithSum as $column) {
            $this->addColumnSum($column, $resultRow, $firstChitRow);
        }
    }

    /**
     * @param Category[] $categories
     * @throws \PHPExcel_Exception
     */
    private function addCategoriesHeader(int $startColumn, string $groupName, array $categories): void
    {
        $lastColumn = $startColumn + count($categories) - 1;
        $this->sheet->mergeCellsByColumnAndRow($startColumn, self::HEADER_ROW, $lastColumn, self::HEADER_ROW);
        $this->sheet->setCellValueByColumnAndRow($startColumn, self::HEADER_ROW, $groupName);

        foreach($categories as $index => $category) {
            $column = $startColumn + $index;

            $cell = $this->sheet->getCellByColumnAndRow($column, self::SUBHEADER_ROW);
            $cell->setValue($category->getName());
            $cell->getStyle()
                ->getAlignment()
                ->setWrapText(TRUE);

            $this->guessColumnWidth(
                $this->sheet->getColumnDimensionByColumn($column),
                $category->getName()
            );
        }
    }

    /**
     * @param Row[] $chits
     * @param Category[] $categories
     */
    private function addChits(array $chits, array $categories): void
    {
        $categories = array_map(function (Category $c) { return $c->getId(); }, $categories);
        $categoryColumns = array_flip($categories);
        $categoryColumns = array_map(function (int $column) {
            return $column + self::CATEGORIES_FIRST_COLUMN;
        }, $categoryColumns);

        $row = self::SUBHEADER_ROW + 1;
        $index = 1;

        $balance = 0;
        foreach ($chits as $chit) {
            $balance += $chit->ctype === 'in' ? $chit->price : -$chit->price;

            $cashbookColumns = [
                $index++,
                $chit->date->format('d.m.'),
                $chit->num,
                $chit->purpose,
                $chit->ctype === 'in' ? $chit->price : '',
                $chit->ctype === 'out' ? $chit->price : '',
                $balance,
            ];

            foreach($cashbookColumns as $column => $value) {
                $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
            }

            $this->sheet->setCellValueByColumnAndRow($categoryColumns[$chit->category], $row, $chit->price);
            $row++;
        }

        $this->sheet->setCellValueByColumnAndRow(6, $row, $balance);
    }

    /**
     * @return Category[][]
     */
    private function getCategories(ObjectType $type): array
    {
        $categories = $this->categories->findByObjectType($type);
        $incomeCategories = [];
        $expenseCategories = [];

        foreach($categories as $category) {
            if($category->isIncome()) {
                $incomeCategories[] = $category;
            } else {
                $expenseCategories[] = $category;
            }

        }

        return [$incomeCategories, $expenseCategories];
    }

    private function addColumnSum(int $column, int $resultRow, int $firstRow): void
    {
        $lastRow = $resultRow - 1;
        $resultCell = $this->sheet->getCellByColumnAndRow($column, $resultRow);
        $stringColumn = $resultCell->getColumn();

        $resultCell->setValue('=SUM(' . $stringColumn . $firstRow . ':' . $stringColumn . $lastRow . ')');
    }

    private function addTableStyles(int $chitsCount, int $categoriesCount, int $incomeCategoriesCount): void
    {
        $sheet = $this->sheet;

        $sheet->mergeCellsByColumnAndRow(0, 2, 3, 2);

        $lastRow = self::SUBHEADER_ROW + $chitsCount + 1;
        $lastColumn = self::CATEGORIES_FIRST_COLUMN + $categoriesCount - 1;

        $sheet->getRowDimension(self::SUBHEADER_ROW)->setRowHeight(40);

        $sheet->getStyleByColumnAndRow(0, 0, 0, $lastRow)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimensionByColumn(0)->setAutoSize(TRUE);
        $sheet->getColumnDimensionByColumn(1)->setAutoSize(TRUE);
        $sheet->getColumnDimensionByColumn(2)->setAutoSize(TRUE);
        $sheet->getColumnDimensionByColumn(3)->setAutoSize(TRUE);
        $sheet->getColumnDimensionByColumn(4)->setAutoSize(TRUE);
        $sheet->getColumnDimensionByColumn(5)->setAutoSize(TRUE);
        $sheet->getColumnDimensionByColumn(6)->setAutoSize(TRUE);

        $header = $sheet->getStyleByColumnAndRow(0, self::HEADER_ROW, $lastColumn, self::HEADER_ROW);

        $header->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $header->getFont()->setBold(TRUE);

        $wholeTable = $sheet->getStyleByColumnAndRow(0, self::HEADER_ROW, $lastColumn, $lastRow);

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
        $lastRowStyle->getFont()->setBold(TRUE);
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

        foreach($separatedColumns as $column) {
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

    private function guessColumnWidth(ColumnDimension $column, string $content): void
    {
        $words = explode(' ', $content);

        if(count($words) <= 1) {
            $column->setAutoSize(TRUE);
            return;
        }

        uasort($words, function(string $a, string $b) { return mb_strlen($a) <=> mb_strlen($b); });

        $width = mb_strlen($words[0]) * self::COLUMN_WIDTH_COEFFICIENT;
        $column->setWidth($width);
    }

}
