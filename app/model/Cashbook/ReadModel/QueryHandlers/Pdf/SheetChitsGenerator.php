<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers\Pdf;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Event\ExportedCashbook;
use Model\Excel\Range;
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
     * @param ExportedCashbook[] $cashbooks
     */
    public function __invoke(Worksheet $sheet, array $cashbooks) : void
    {
        $sheet->setCellValue('A1', 'Název akce')
            ->setCellValue('B1', 'Ze dne')
            ->setCellValue('C1', 'Způsob')
            ->setCellValue('D1', 'Číslo dokladu')
            ->setCellValue('E1', 'Účel výplaty')
            ->setCellValue('F1', 'Kategorie')
            ->setCellValue('G1', 'Komu/Od')
            ->setCellValue('H1', 'Příjem')
            ->setCellValue('I1', 'Výdej');

        $rowCnt = 2;
        foreach ($cashbooks as $item) {
            $cashbookId = $item->getCashbookId();
            $cashbook   = $this->queryBus->handle(new CashbookQuery($cashbookId));

            assert($cashbook instanceof Cashbook);

            foreach ($this->queryBus->handle(ChitListQuery::all($cashbookId)) as $chit) {
                assert($chit instanceof Chit);

                $isIncome = $chit->isIncome();
                $amount   = $chit->getAmount()->toFloat();
                $prefix   = $cashbook->getChitNumberPrefix($chit->getPaymentMethod());

                $sheet->setCellValue('A' . $rowCnt, $item->getDisplayName())
                    ->setCellValue('B' . $rowCnt, $chit->getDate()->format('d.m.Y'))
                    ->setCellValue('C' . $rowCnt, $chit->getPaymentMethod()->equals(PaymentMethod::CASH()) ? 'Pokladna' : 'Banka')
                    ->setCellValue('D' . $rowCnt, $prefix . (string) $chit->getNumber())
                    ->setCellValue('E' . $rowCnt, $chit->getPurpose())
                    ->setCellValue('F' . $rowCnt, $chit->getCategories())
                    ->setCellValue('G' . $rowCnt, (string) $chit->getRecipient())
                    ->setCellValue('H' . $rowCnt, $isIncome ? $amount : '')
                    ->setCellValue('I' . $rowCnt, ! $isIncome ? $amount : '');

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
