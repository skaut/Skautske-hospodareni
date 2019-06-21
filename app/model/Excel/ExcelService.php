<?php

declare(strict_types=1);

namespace Model;

use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Participant\Participant;
use Model\Excel\Builders\CashbookWithCategoriesBuilder;
use Model\Excel\Range;
use Nette\Utils\ArrayHash;
use PHPExcel;
use PHPExcel_Exception;
use PHPExcel_Style_Border;
use PHPExcel_Worksheet;
use PHPExcel_Writer_Excel2007;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use stdClass;
use function assert;
use function gmdate;
use function header;

class ExcelService
{
    private const ADULT_AGE = 18;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    protected function getNewFile() : PHPExcel
    {
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator('h.skauting.cz')
            ->setLastModifiedBy('h.skauting.cz');

        return $objPHPExcel;
    }

    private function getNewFileV2() : Spreadsheet
    {
        $sheet = new Spreadsheet();
        $sheet->getProperties()
            ->setCreator('h.skauting.cz')
            ->setLastModifiedBy('h.skauting.cz');

        return $sheet;
    }

    public function getParticipants(EventEntity $service, stdClass $event, string $type) : Spreadsheet
    {
        $spreadsheet = $this->getNewFileV2();
        $data        = $service->getParticipants()->getAll($event->ID);
        $sheet       = $spreadsheet->getActiveSheet();
        if ($type === 'camp') {
            $this->setSheetParticipantCamp($sheet, $data);
        } else {//GENERAL EVENT
            $this->setSheetParticipantGeneral($sheet, $data, $event);
        }

        return $spreadsheet;
    }

    public function getCashbook(CashbookId $cashbookId, PaymentMethod $paymentMethod) : Spreadsheet
    {
        $objPHPExcel = $this->getNewFileV2();
        $sheet       = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetCashbook($sheet, $cashbookId, $paymentMethod);

        return $objPHPExcel;
    }

    public function getCashbookWithCategories(CashbookId $cashbookId, PaymentMethod $paymentMethod) : Spreadsheet
    {
        $excel = $this->getNewFileV2();
        $sheet = $excel->getActiveSheet();

        $builder = new CashbookWithCategoriesBuilder($this->queryBus);
        $builder->build($sheet, $cashbookId, $paymentMethod);

        return $excel;
    }

    /**
     * @param Chit[] $chits
     */
    public function getChitsExport(CashbookId $cashbookId, array $chits) : Spreadsheet
    {
        $spreadsheet = $this->getNewFileV2();
        $sheetChit   = $spreadsheet->setActiveSheetIndex(0);
        $this->setSheetChitsOnly($sheetChit, $chits, $cashbookId);

        return $spreadsheet;
    }

    /**
     * @param Participant[] $data
     *
     * @throws PHPExcel_Exception
     */
    protected function setSheetParticipantCamp(Worksheet $sheet, array $data) : void
    {
        $sheet->setCellValue('A1', 'P.č.')
            ->setCellValue('B1', 'Jméno')
            ->setCellValue('C1', 'Příjmení')
            ->setCellValue('D1', 'Příjmení')
            ->setCellValue('E1', 'Ulice')
            ->setCellValue('F1', 'Město')
            ->setCellValue('G1', 'PSČ')
            ->setCellValue('H1', 'Datum narození')
            ->setCellValue('I1', 'Osobodny')
            ->setCellValue('J1', 'Dětodny')
            ->setCellValue('K1', 'Zaplaceno')
            ->setCellValue('L1', 'Vratka')
            ->setCellValue('M1', 'Celkem')
            ->setCellValue('N1', 'Na účet');

        $rowCnt = 2;

        /** @var Participant $row */
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowCnt, ($rowCnt - 1))
                ->setCellValue('B' . $rowCnt, $row->getFirstName())
                ->setCellValue('C' . $rowCnt, $row->getLastName())
                ->setCellValue('D' . $rowCnt, $row->getNickName())
                ->setCellValue('E' . $rowCnt, $row->getStreet())
                ->setCellValue('F' . $rowCnt, $row->getCity())
                ->setCellValue('G' . $rowCnt, $row->getPostcode())
                ->setCellValue('H' . $rowCnt, $row->getBirthday() !== null ? $row->getBirthday()->format('d.m.Y') : '')
                ->setCellValue('I' . $rowCnt, $row->getDays())
                ->setCellValue('J' . $rowCnt, $row->getAge() < self::ADULT_AGE ? $row->getDays() : 0)
                ->setCellValue('K' . $rowCnt, $row->getPayment())
                ->setCellValue('L' . $rowCnt, $row->getRepayment())
                ->setCellValue('M' . $rowCnt, ($row->getPayment() - $row->getRepayment()))
                ->setCellValue('N' . $rowCnt, $row->getOnAccount() === 'Y' ? 'Ano' : 'Ne');
            $rowCnt++;
        }
        //format
        foreach (Range::letters('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:N' . ($rowCnt - 1));
    }

    /**
     * @param Participant[] $data
     *
     * @throws PHPExcel_Exception
     */
    protected function setSheetParticipantGeneral(Worksheet $sheet, array $data, stdClass $event) : void
    {
        $startDate = new Date($event->StartDate);
        $sheet->setCellValue('A1', 'P.č.')
            ->setCellValue('B1', 'Jméno')
            ->setCellValue('C1', 'Příjmení')
            ->setCellValue('D1', 'Přezdívka')
            ->setCellValue('E1', 'Ulice')
            ->setCellValue('F1', 'Město')
            ->setCellValue('G1', 'PSČ')
            ->setCellValue('H1', 'Datum narození')
            ->setCellValue('I1', 'Osobodny')
            ->setCellValue('J1', 'Dětodny')
            ->setCellValue('K1', 'Zaplaceno');

        $rowCnt = 2;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowCnt, ($rowCnt - 1))
                ->setCellValue('B' . $rowCnt, $row->getFirstName())
                ->setCellValue('C' . $rowCnt, $row->getLastName())
                ->setCellValue('D' . $rowCnt, $row->getNickName())
                ->setCellValue('E' . $rowCnt, $row->getStreet())
                ->setCellValue('F' . $rowCnt, $row->getCity())
                ->setCellValue('G' . $rowCnt, $row->getPostcode())
                ->setCellValue('H' . $rowCnt, $row->getBirthday() !== null ? $row->getBirthday()->format('d.m.Y') : '')
                ->setCellValue('I' . $rowCnt, $row->getDays())
                ->setCellValue('J' . $rowCnt, $row->getBirthday() !== null && $startDate->diffInYears($row->getBirthday()) < self::ADULT_AGE ? $row->getDays() : 0)
                ->setCellValue('K' . $rowCnt, $row->getPayment());
            $rowCnt++;
        }
        //format
        foreach (Range::letters('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:J' . ($rowCnt - 1));
        $sheet->setTitle('Seznam účastníků');
    }

    private function setSheetCashbook(Worksheet $sheet, CashbookId $cashbookId, PaymentMethod $paymentMethod) : void
    {
        $sheet->setCellValue('A1', 'Ze dne')
            ->setCellValue('B1', 'Číslo dokladu')
            ->setCellValue('C1', 'Účel platby')
            ->setCellValue('D1', 'Kategorie')
            ->setCellValue('E1', 'Komu/od')
            ->setCellValue('F1', 'Příjem')
            ->setCellValue('G1', 'Výdej')
            ->setCellValue('H1', 'Zůstatek');

        $chits    = $this->queryBus->handle(ChitListQuery::withMethod($paymentMethod, $cashbookId));
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        $prefix        = $cashbook->getChitNumberPrefix();
        $categoryNames = $this->queryBus->handle(new CategoryPairsQuery($cashbookId));

        $balance = 0;
        $rowCnt  = 2;
        foreach ($chits as $chit) {
            assert($chit instanceof Chit);

            $isIncome = $chit->isIncome();
            $amount   = $chit->getAmount()->toFloat();

            $balance += $isIncome ? $amount : -$amount;

            $sheet->setCellValue('A' . $rowCnt, $chit->getDate()->format('d.m.Y'))
                ->setCellValue('B' . $rowCnt, $prefix . $chit->getNumber())
                ->setCellValue('C' . $rowCnt, $chit->getPurpose())
                ->setCellValue('D' . $rowCnt, $chit->getCategories())
                ->setCellValue('E' . $rowCnt, (string) $chit->getRecipient())
                ->setCellValue('F' . $rowCnt, $isIncome ? $amount : '')
                ->setCellValue('G' . $rowCnt, ! $isIncome ? $amount : '')
                ->setCellValue('H' . $rowCnt, $balance);
            $rowCnt++;
        }

        //format
        foreach (Range::letters('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('H1:H' . ($rowCnt - 1))->getFont()->setBold(true);
        // $sheet->setAutoFilter('A1:H' . ($rowCnt - 1));

        $sheet->setTitle('Evidence plateb');
    }

    /**
     * @param ArrayHash[] $data
     *
     * @throws PHPExcel_Exception
     */
    private function setSheetChits(PHPExcel_Worksheet $sheet, array $data) : void
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

                $sheet->setCellValue('A' . $rowCnt, $event->DisplayName)
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

    /**
     * @param Chit[] $chits
     */
    private function setSheetChitsOnly(Worksheet $sheet, array $chits, CashbookId $cashbookId) : void
    {
        $sheet->setCellValue('B1', 'Ze dne')
            ->setCellValue('C1', 'Účel výplaty')
            ->setCellValue('D1', 'Kategorie')
            ->setCellValue('E1', 'Komu/Od')
            ->setCellValue('F1', 'Částka')
            ->setCellValue('G1', 'Typ');

        $rowCnt        = 2;
        $sumIn         = $sumOut = 0;
        $categoryNames = $this->queryBus->handle(new CategoryPairsQuery($cashbookId));

        foreach ($chits as $chit) {
            $amount = $chit->getAmount()->toFloat();

            $sheet->setCellValue('A' . $rowCnt, $rowCnt - 1)
                ->setCellValue('B' . $rowCnt, $chit->getDate()->format('d.m.Y'))
                ->setCellValue('C' . $rowCnt, $chit->getPurpose())
                ->setCellValue('D' . $rowCnt, $chit->getCategories())
                ->setCellValue('E' . $rowCnt, $chit->getRecipient())
                ->setCellValue('F' . $rowCnt, $amount)
                ->setCellValue('G' . $rowCnt, $chit->isIncome() ? 'Příjem' : 'Výdaj');

            if ($chit->isIncome()) {
                $sumIn += $amount;
            } else {
                $sumOut += $amount;
            }

            $rowCnt++;
        }
        //add border
        $sheet->getStyle('A1:G' . ($rowCnt - 1))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

        if ($sumIn > 0) {
            $rowCnt++;
            $sheet->setCellValue('E' . $rowCnt, 'Příjmy')
                ->setCellValue('F' . $rowCnt, $sumIn);
            $sheet->getStyle('E' . $rowCnt)->getFont()->setBold(true);
        }
        if ($sumOut > 0) {
            $rowCnt++;
            $sheet->setCellValue('E' . $rowCnt, 'Výdaje')
                ->setCellValue('F' . $rowCnt, $sumOut);
            $sheet->getStyle('E' . $rowCnt)->getFont()->setBold(true);
        }

        //format
        foreach (Range::letters('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->setTitle('Doklady');
    }

    protected function send(PHPExcel $obj, string $filename) : void
    {
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = new PHPExcel_Writer_Excel2007($obj);
        $objWriter->setPreCalculateFormulas(true);
        $objWriter->save('php://output');
        //exit;
    }
}
