<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers\Pdf;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\Excel\Range;
use Nette\Utils\ArrayHash;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use function assert;

class SheetChitsGenerator
{
    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    /**
     * @param ArrayHash[] $data
     */
    public function __invoke(Worksheet $sheet, array $data) : void
    {
        $sheet->setCellValue('A1', 'Název akce')
            ->setCellValue('B1', 'Ze dne')
            ->setCellValue('C1', 'Číslo dokladu')
            ->setCellValue('D1', 'Účel výplaty')
            ->setCellValue('E1', 'Kategorie')
            ->setCellValue('F1', 'Komu/Od')
            ->setCellValue('G1', 'Příjem')
            ->setCellValue('H1', 'Výdej');

        $rowCnt = 2;
        foreach ($data as $event) {
            $cashbookId = $event['cashbookId'];
            $cashbook   = $this->queryBus->handle(new CashbookQuery($cashbookId));

            assert($cashbook instanceof Cashbook);

            $prefix = $cashbook->getChitNumberPrefix();

            foreach ($event['chits'] as $chit) {
                assert($chit instanceof Chit);

                $isIncome = $chit->isIncome();
                $amount   = $chit->getAmount()->toFloat();

                $sheet->setCellValue('A' . $rowCnt, $event['event']->getDisplayName())
                    ->setCellValue('B' . $rowCnt, $chit->getDate()->format('d.m.Y'))
                    ->setCellValue('C' . $rowCnt, $prefix . (string) $chit->getNumber())
                    ->setCellValue('D' . $rowCnt, $chit->getPurpose())
                    ->setCellValue('E' . $rowCnt, $chit->getCategories())
                    ->setCellValue('F' . $rowCnt, (string) $chit->getRecipient())
                    ->setCellValue('G' . $rowCnt, $isIncome ? $amount : '')
                    ->setCellValue('H' . $rowCnt, ! $isIncome ? $amount : '');

                $rowCnt++;
            }
        }

        //format
        foreach (Range::letters('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:H' . ($rowCnt - 1));
        $sheet->setTitle('Doklady');
    }
}
