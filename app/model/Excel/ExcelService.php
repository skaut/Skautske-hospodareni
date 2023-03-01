<?php

declare(strict_types=1);

namespace Model;

use App\AccountancyModule\AccountancyHelpers;
use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Participant\Participant;
use Model\Excel\Builders\CashbookWithCategoriesBuilder;
use Model\Excel\Range;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use function assert;

class ExcelService
{
    private const ADULT_AGE = 18;

    public function __construct(private QueryBus $queryBus)
    {
    }

    private function getNewFile(): Spreadsheet
    {
        $sheet = new Spreadsheet();
        $sheet->getProperties()
            ->setCreator('h.skauting.cz')
            ->setLastModifiedBy('h.skauting.cz');

        return $sheet;
    }

    /** @param Participant[] $participantsDTO */
    public function getGeneralParticipants(array $participantsDTO, Date $startDate): Spreadsheet
    {
        $spreadsheet = $this->getNewFile();
        $sheet       = $spreadsheet->getActiveSheet();
        $this->setSheetParticipantGeneral($sheet, $participantsDTO, $startDate);

        return $spreadsheet;
    }

    /** @param Participant[] $participantsDTO */
    public function getCampParticipants(array $participantsDTO): Spreadsheet
    {
        $spreadsheet = $this->getNewFile();
        $sheet       = $spreadsheet->getActiveSheet();
        $this->setSheetParticipantCamp($sheet, $participantsDTO);

        return $spreadsheet;
    }

    public function getCashbook(CashbookId $cashbookId, PaymentMethod $paymentMethod): Spreadsheet
    {
        $spreadsheet = $this->getNewFile();
        $sheet       = $spreadsheet->setActiveSheetIndex(0);
        $this->setSheetCashbook($sheet, $cashbookId, $paymentMethod);

        return $spreadsheet;
    }

    public function getCashbookWithCategories(CashbookId $cashbookId, PaymentMethod $paymentMethod): Spreadsheet
    {
        $excel = $this->getNewFile();
        $sheet = $excel->getActiveSheet();

        $builder = new CashbookWithCategoriesBuilder($this->queryBus);
        $builder->build($sheet, $cashbookId, $paymentMethod);

        return $excel;
    }

    public function getCashbookItems(CashbookId $cashbookId, PaymentMethod $paymentMethod): Spreadsheet
    {
        $excel = $this->getNewFile();
        $sheet = $excel->getActiveSheet();

        $builder = new CashbookWithCategoriesBuilder($this->queryBus);
        $builder->build($sheet, $cashbookId, $paymentMethod);

        return $excel;
    }

    /** @param Chit[] $chits */
    public function getChitsExport(array $chits): Spreadsheet
    {
        $spreadsheet = $this->getNewFile();
        $sheetChit   = $spreadsheet->setActiveSheetIndex(0);
        $this->setSheetChitsOnly($sheetChit, $chits);

        return $spreadsheet;
    }

    /** @param Chit[] $chits */
    public function addItemsExport(Spreadsheet $spreadsheetWithActiveSheet, array $chits): Spreadsheet
    {
        $this->setSheetItemsOnly($spreadsheetWithActiveSheet->getActiveSheet(), $chits);

        return $spreadsheetWithActiveSheet;
    }

    /** @param Participant[] $data */
    protected function setSheetParticipantCamp(Worksheet $sheet, array $data): void
    {
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
            ->setCellValue('K1', 'Zaplaceno')
            ->setCellValue('L1', 'Vratka')
            ->setCellValue('M1', 'Celkem')
            ->setCellValue('N1', 'Na účet');

        $rowCnt = 2;

        foreach ($data as $row) {
            assert($row instanceof Participant);
            $sheet->setCellValue('A' . $rowCnt, $rowCnt - 1)
                ->setCellValue('B' . $rowCnt, $row->getFirstName())
                ->setCellValue('C' . $rowCnt, $row->getLastName())
                ->setCellValue('D' . $rowCnt, $row->getNickName())
                ->setCellValue('E' . $rowCnt, $row->getStreet())
                ->setCellValue('F' . $rowCnt, $row->getCity())
                ->setCellValue('G' . $rowCnt, $row->getPostcode())
                ->setCellValue('H' . $rowCnt, $row->getBirthday()?->format('d.m.Y') ?? '')
                ->setCellValue('I' . $rowCnt, $row->getDays())
                ->setCellValue('J' . $rowCnt, $row->getAge() < self::ADULT_AGE ? $row->getDays() : 0)
                ->setCellValue('K' . $rowCnt, $row->getPayment())
                ->setCellValue('L' . $rowCnt, $row->getRepayment())
                ->setCellValue('M' . $rowCnt, $row->getPayment() - $row->getRepayment())
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

    /** @param Participant[] $data */
    protected function setSheetParticipantGeneral(Worksheet $sheet, array $data, Date $startDate): void
    {
        $sheet->setCellValue('A1', 'P.č.')
            ->setCellValue('B1', 'Jméno')
            ->setCellValue('C1', 'Příjmení')
            ->setCellValue('D1', 'Přezdívka')
            ->setCellValue('E1', 'Ulice')
            ->setCellValue('F1', 'Město')
            ->setCellValue('G1', 'PSČ')
            ->setCellValue('H1', 'Datum narození')
            ->setCellValue('I1', 'Jednotka')
            ->setCellValue('J1', 'Osobodny')
            ->setCellValue('K1', 'Dětodny')
            ->setCellValue('L1', 'Zaplaceno');

        $rowCnt = 2;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowCnt, $rowCnt - 1)
                ->setCellValue('B' . $rowCnt, $row->getFirstName())
                ->setCellValue('C' . $rowCnt, $row->getLastName())
                ->setCellValue('D' . $rowCnt, $row->getNickName())
                ->setCellValue('E' . $rowCnt, $row->getStreet())
                ->setCellValue('F' . $rowCnt, $row->getCity())
                ->setCellValue('G' . $rowCnt, $row->getPostcode())
                ->setCellValue('H' . $rowCnt, $row->getBirthday()?->format('d.m.Y') ?? '')
                ->setCellValue('I' . $rowCnt, $row->getUnitRegistrationNumber())
                ->setCellValue('J' . $rowCnt, $row->getDays())
                ->setCellValue('K' . $rowCnt, $row->getBirthday() !== null && $startDate->diffInYears($row->getBirthday()) < self::ADULT_AGE ? $row->getDays() : 0)
                ->setCellValue('L' . $rowCnt, $row->getPayment());
            $rowCnt++;
        }

        //format
        foreach (Range::letters('A', 'L') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:L' . ($rowCnt - 1));
        $sheet->setTitle('Seznam účastníků');
    }

    private function setSheetCashbook(Worksheet $sheet, CashbookId $cashbookId, PaymentMethod $paymentMethod): void
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

        $balance = 0;
        $rowCnt  = 2;
        foreach ($chits as $chit) {
            assert($chit instanceof Chit);

            $isIncome = $chit->isIncome();
            $amount   = $chit->getAmount()->toFloat();
            $prefix   = $cashbook->getChitNumberPrefix($chit->getPaymentMethod());

            $balance += $isIncome ? $amount : -$amount;

            $sheet->setCellValue('A' . $rowCnt, $chit->getBody()->getDate()->format('d.m.Y'))
                ->setCellValue('B' . $rowCnt, $prefix . $chit->getBody()->getNumber())
                ->setCellValue('C' . $rowCnt, $chit->getPurpose())
                ->setCellValue('D' . $rowCnt, $chit->getCategories())
                ->setCellValue('E' . $rowCnt, (string) $chit->getBody()->getRecipient())
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

    /** @param Chit[] $chits */
    private function setSheetChitsOnly(Worksheet $sheet, array $chits): void
    {
        $sheet->setCellValue('B1', 'Ze dne')
            ->setCellValue('C1', 'Účel výplaty')
            ->setCellValue('D1', 'Kategorie')
            ->setCellValue('E1', 'Komu/Od')
            ->setCellValue('F1', 'Částka')
            ->setCellValue('G1', 'Typ');

        $rowCnt = 2;
        $sumIn  = $sumOut = 0;

        foreach ($chits as $chit) {
            $amount = $chit->getAmount()->toFloat();

            $sheet->setCellValue('A' . $rowCnt, $rowCnt - 1)
                ->setCellValue('B' . $rowCnt, $chit->getBody()->getDate()->format('d.m.Y'))
                ->setCellValue('C' . $rowCnt, $chit->getPurpose())
                ->setCellValue('D' . $rowCnt, $chit->getCategories())
                ->setCellValue('E' . $rowCnt, $chit->getBody()->getRecipient())
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
        $sheet->getStyle('A1:G' . ($rowCnt - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

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

    /** @param Chit[] $chits */
    private function setSheetItemsOnly(Worksheet $sheet, array $chits): void
    {
        $sheet->setCellValue('B1', 'Ze dne')
            ->setCellValue('C1', 'Číslo dokladu')
            ->setCellValue('D1', 'Účel výplaty')
            ->setCellValue('E1', 'Kategorie')
            ->setCellValue('F1', 'Komu/Od')
            ->setCellValue('G1', 'Částka');

        $rowCnt = 2;

        foreach ($chits as $chit) {
            foreach ($chit->getItems() as $item) {
                $amount = $item->getAmount()->toFloat();

                $sheet->setCellValue('A' . $rowCnt, $rowCnt - 1)
                    ->setCellValue('B' . $rowCnt, $chit->getBody()->getDate()->format('d.m.Y'))
                    ->setCellValue('C' . $rowCnt, $chit->getBody()->getNumber())
                    ->setCellValue('D' . $rowCnt, $item->getPurpose())
                    ->setCellValue('E' . $rowCnt, $item->getCategory()->getName())
                    ->setCellValue('F' . $rowCnt, $chit->getBody()->getRecipient())
                    ->setCellValue('G' . $rowCnt, $amount);
                $rowCnt++;
            }
        }

        //add border
        $sheet->getStyle('A1:G' . ($rowCnt - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        //format
        foreach (Range::letters('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->setTitle('Položky z dokladů');
    }

    /**
     * @param DTO\Payment\Payment[] $paymentsDTO
     *
     * @throws Exception
     */
    public function getPaymentsList(array $paymentsDTO, string $paymentGroupName): Spreadsheet
    {
        $spreadsheet = $this->getNewFile();
        $sheet       = $spreadsheet->getActiveSheet();
        $this->setSheetPaymentList($sheet, $paymentsDTO);
        $sheet->setTitle($paymentGroupName);

        return $spreadsheet;
    }

    /**
     * @param DTO\Payment\Payment[] $paymentsDTO
     *
     * @throws Exception
     */
    private function setSheetPaymentList(Worksheet $sheet, array $paymentsDTO): void
    {
        $sheet->setCellValue('B1', 'Název/účel')
            ->setCellValue('C1', 'E-mail')
            ->setCellValue('D1', 'Částka')
            ->setCellValue('E1', 'VS')
            ->setCellValue('F1', 'Splatnost')
            ->setCellValue('G1', 'Stav');

        $rowCnt = 2;

        foreach ($paymentsDTO as $payment) {
                $sheet->setCellValue('A' . $rowCnt, $rowCnt - 1)
                    ->setCellValue('B' . $rowCnt, $payment->getName())
                    ->setCellValue('C' . $rowCnt, $payment->getRecipientsString())
                    ->setCellValue('D' . $rowCnt, $payment->getAmount())
                    ->setCellValue('E' . $rowCnt, $payment->getVariableSymbol())
                    ->setCellValue('F' . $rowCnt, $payment->getDueDate()->format('d.m.Y'))
                    ->setCellValue('G' . $rowCnt, AccountancyHelpers::paymentState($payment->getState()->getValue(), false));
                $rowCnt++;
        }

        //add border
        $sheet->getStyle('A1:G' . ($rowCnt - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        //format
        foreach (Range::letters('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
    }
}
