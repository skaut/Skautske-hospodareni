<?php

namespace Model\Excel\Builders;

use Dibi\Row;
use Model\Cashbook\Category;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Repositories\ICategoryRepository;
use Model\EventEntity;

class CashbookWithCategoriesBuilder
{

    /** @var ICategoryRepository */
    private $categories;

    /** @var \PHPExcel_Worksheet */
    private $sheet;

    private const HEADER_ROW = 2;
    private const SUBHEADER_ROW = 3;
    private const CATEGORIES_FIRST_COLUMN = 6;

    public function __construct(ICategoryRepository $categories)
    {
        $this->categories = $categories;
    }

    public function build(\PHPExcel_Worksheet $sheet, EventEntity $eventEntity, int $eventId, ObjectType $type): void
    {
        $this->sheet = $sheet;
        $sheet->setCellValue('A1', 'Pokladní kniha');
        $sheet->mergeCells('D2:F2');
        $sheet->setCellValue('D2', 'Pokladní kniha');

        $sheet->setCellValue('A3', 'Dne');
        $sheet->setCellValue('B3', 'Dokl.');
        $sheet->setCellValue('C3', 'Účel platby');
        $sheet->setCellValue('D3', 'Příjem');
        $sheet->setCellValue('E3', 'Výdaj');
        $sheet->setCellValue('F3', 'Zůstatek');

        [$incomeCategories, $expenseCategories] = $this->getCategories($type);
        $this->addCategoriesHeader(self::CATEGORIES_FIRST_COLUMN, 'Příjmy', $incomeCategories);
        $expensesFirstColumn = self::CATEGORIES_FIRST_COLUMN + count($incomeCategories);
        $this->addCategoriesHeader($expensesFirstColumn, 'Výdaje', $expenseCategories);

        $chits = $eventEntity->chits->getAll($eventId);
        $categories = array_merge($incomeCategories, $expenseCategories);
        $this->addChits($chits, $categories);

        $firstChitRow = self::SUBHEADER_ROW + 1;
        $resultRow = self::SUBHEADER_ROW + count($chits) + 1;

        for($i = 0; $i < count($categories); $i++) {
            $this->addColumnSum(self::CATEGORIES_FIRST_COLUMN + $i, $resultRow, $firstChitRow);
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
            $this->sheet->setCellValueByColumnAndRow(
                $startColumn + $index,
                self::SUBHEADER_ROW,
                $category->getName()
            );
        }
    }

    /**
     * @param Row[] $chits
     * @param Category[] $row
     */
    private function addChits(array $chits, array $categories): void
    {
        $categories = array_map(function (Category $c) { return $c->getId(); }, $categories);
        $categoryColumns = array_flip($categories);
        $categoryColumns = array_map(function (int $column) {
            return $column + self::CATEGORIES_FIRST_COLUMN;
        }, $categoryColumns);

        $row = self::SUBHEADER_ROW + 1;
        $balance = 0;
        foreach ($chits as $chit) {
            $balance += $chit->ctype === 'in' ? $chit->price : -$chit->price;
            $cashbookColumns = [
                'A' => $chit->date->format('d.m.'),
                'B' => $chit->num,
                'C' => $chit->purpose,
                'D' => $chit->ctype === 'in' ? $chit->price : '',
                'E' => $chit->ctype === 'out' ? $chit->price : '',
                'F' => $balance,
            ];

            foreach($cashbookColumns as $column => $value) {
                $this->sheet->setCellValue($column . $row, $value);
            }

            $this->sheet->setCellValueByColumnAndRow($categoryColumns[$chit->category], $row, $chit->price);
        }

        $this->sheet->setCellValue('F' . ++$row, $balance);
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

}
