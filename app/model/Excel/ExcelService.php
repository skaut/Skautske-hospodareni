<?php

declare(strict_types=1);

namespace Model;

use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Participant\Participant;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Excel\Builders\CashbookWithCategoriesBuilder;
use Model\Participant\PragueParticipants;
use Nette\Utils\Strings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Skautis\Wsdl\PermissionException;
use function count;
use function date;
use function gmdate;
use function header;
use function implode;
use function range;
use function reset;
use function strtotime;

class ExcelService
{
    private const ADULT_AGE = 18;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(QueryBus $queryBus)
    {
        $this->queryBus = $queryBus;
    }

    protected function getNewFile() : \PHPExcel
    {
        $objPHPExcel = new \PHPExcel();
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

    public function getParticipants(EventEntity $service, \stdClass $event, string $type) : void
    {
        $objPHPExcel = $this->getNewFile();
        $data        = $service->getParticipants()->getAll($event->ID);
        $sheet       = $objPHPExcel->getActiveSheet();
        if ($type === 'camp') {
            $this->setSheetParticipantCamp($sheet, $data);
        } else {//GENERAL EVENT
            $this->setSheetParticipantGeneral($sheet, $data, $event);
        }
        $this->send($objPHPExcel, Strings::webalize($event->DisplayName) . '-' . date('Y_n_j'));
    }

    public function getCashbook(string $cashbookName, CashbookId $cashbookId, PaymentMethod $paymentMethod) : void
    {
        $objPHPExcel = $this->getNewFile();
        $sheet       = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetCashbook($sheet, $cashbookId, $paymentMethod);
        $this->send($objPHPExcel, Strings::webalize($cashbookName) . '-pokladni-kniha-' . date('Y_n_j'));
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
     * @param int[] $eventIds
     */
    public function getEventSummaries(array $eventIds, EventEntity $service) : void
    {
        $objPHPExcel = $this->getNewFile();

        $allowPragueColumns = false;
        $data               = [];
        foreach ($eventIds as $aid) {
            $eventId = new SkautisEventId($aid);
            /** @var CashbookId $cashbookId */
            $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery($eventId));

            $data[$aid]                    = $service->getEvent()->get($aid);
            $data[$aid]['cashbookId']      = $cashbookId;
            $data[$aid]['parStatistic']    = $service->getParticipants()->getEventStatistic($aid);
            $data[$aid]['chits']           = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId));
            $data[$aid]['func']            = $this->queryBus->handle(new EventFunctions($eventId));
            $participants                  = $service->getParticipants()->getAll($aid);
            $data[$aid]['participantsCnt'] = count($participants);
            $data[$aid]['personDays']      = $service->getParticipants()->getPersonsDays($participants);
            $pp                            = $service->getParticipants()->countPragueParticipants($data[$aid]);
            if ($pp === null) {
                continue;
            }
            //Prague event
            $allowPragueColumns               = true;
            $data[$aid]['pragueParticipants'] = $pp;
        }
        $sheetEvents = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetEvents($sheetEvents, $data, $allowPragueColumns);
        $objPHPExcel->createSheet(1);
        $sheetChit = $objPHPExcel->setActiveSheetIndex(1);
        $this->setSheetChits($sheetChit, $data);
        $this->send($objPHPExcel, 'Souhrn-akcí-' . date('Y_n_j'));
    }

    /**
     * @param int[] $campsIds
     * @throws \PHPExcel_Exception
     * @throws PermissionException
     */
    public function getCampsSummary(array $campsIds, EventEntity $service, UnitService $unitService) : void
    {
        $objPHPExcel = $this->getNewFile();

        $data = [];
        foreach ($campsIds as $aid) {
            $campId = new SkautisCampId($aid);
            /** @var CashbookId $cashbookId */
            $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($campId));

            $camp                          = $service->getEvent()->get($aid);
            $data[$aid]                    = $camp;
            $data[$aid]['cashbookId']      = $cashbookId;
            $data[$aid]['troops']          = implode(', ', $unitService->getCampTroopNames($camp));
            $data[$aid]['chits']           = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId));
            $data[$aid]['func']            = $this->queryBus->handle(new CampFunctions(new SkautisCampId($aid)));
            $participants                  = $service->getParticipants()->getAll($aid);
            $data[$aid]['participantsCnt'] = count($participants);
            $data[$aid]['personDays']      = $service->getParticipants()->getPersonsDays($participants);
        }
        $sheetCamps = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetCamps($sheetCamps, $data);
        $objPHPExcel->createSheet(1);
        $sheetChit = $objPHPExcel->setActiveSheetIndex(1);
        $this->setSheetChits($sheetChit, $data);
        $this->send($objPHPExcel, 'Souhrn-táborů-' . date('Y_n_j'));
    }

    /**
     * @param Chit[] $chits
     * @throws \PHPExcel_Exception
     */
    public function getChitsExport(CashbookId $cashbookId, array $chits) : void
    {
        $objPHPExcel = $this->getNewFile();
        $sheetChit   = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetChitsOnly($sheetChit, $chits, $cashbookId);
        $this->send($objPHPExcel, 'Export-vybranych-paragonu');
    }

    /**
     * @param Participant[] $data
     * @throws \PHPExcel_Exception
     */
    protected function setSheetParticipantCamp(\PHPExcel_Worksheet $sheet, array $data) : void
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
        foreach (range('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:N' . ($rowCnt - 1));
    }

    /**
     * @param Participant[] $data
     * @throws \PHPExcel_Exception
     */
    protected function setSheetParticipantGeneral(\PHPExcel_Worksheet $sheet, array $data, \stdClass $event) : void
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
                ->setCellValue('J' . $rowCnt, ($row->getBirthday() !== null && $startDate->diffInYears($row->getBirthday()) < self::ADULT_AGE) ? $row->getDays() : 0)
                ->setCellValue('K' . $rowCnt, $row->getPayment());
            $rowCnt++;
        }
        //format
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:J' . ($rowCnt - 1));
        $sheet->setTitle('Seznam účastníků');
    }

    private function setSheetCashbook(\PHPExcel_Worksheet $sheet, CashbookId $cashbookId, PaymentMethod $paymentMethod) : void
    {
        $sheet->setCellValue('A1', 'Ze dne')
            ->setCellValue('B1', 'Číslo dokladu')
            ->setCellValue('C1', 'Účel platby')
            ->setCellValue('D1', 'Kategorie')
            ->setCellValue('E1', 'Komu/od')
            ->setCellValue('F1', 'Příjem')
            ->setCellValue('G1', 'Výdej')
            ->setCellValue('H1', 'Zůstatek');

        /** @var Chit[] $chits */
        $chits = $this->queryBus->handle(ChitListQuery::withMethod($paymentMethod, $cashbookId));

        /** @var Cashbook $cashbook */
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));
        $prefix   = $cashbook->getChitNumberPrefix();
        /** @var string[] $categoryNames */
        $categoryNames = $this->queryBus->handle(new CategoryPairsQuery($cashbookId));

        $balance = 0;
        $rowCnt  = 2;
        foreach ($chits as $chit) {
            $isIncome = $chit->getCategory()->getOperationType()->equalsValue(Operation::INCOME);
            $amount   = $chit->getAmount()->toFloat();

            $balance += $isIncome ? $amount : -$amount;

            $sheet->setCellValue('A' . $rowCnt, $chit->getDate()->format('d.m.Y'))
                ->setCellValue('B' . $rowCnt, $prefix . $chit->getNumber())
                ->setCellValue('C' . $rowCnt, $chit->getPurpose())
                ->setCellValue('D' . $rowCnt, $categoryNames[$chit->getCategory()->getId()])
                ->setCellValue('E' . $rowCnt, (string) $chit->getRecipient())
                ->setCellValue('F' . $rowCnt, $isIncome ? $amount : '')
                ->setCellValue('G' . $rowCnt, ! $isIncome ? $amount : '')
                ->setCellValue('H' . $rowCnt, $balance);
            $rowCnt++;
        }

        //format
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('H1:H' . ($rowCnt - 1))->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:H' . ($rowCnt - 1));

        $sheet->setTitle('Evidence plateb');
    }

    /**
     * @param mixed[] $data
     * @throws \PHPExcel_Exception
     */
    protected function setSheetEvents(\PHPExcel_Worksheet $sheet, array $data, bool $allowPragueColumns = false) : void
    {
        $firstElement = reset($data);

        $sheet->setCellValue('A1', 'Pořadatel')
            ->setCellValue('B1', 'Název akce')
            ->setCellValue('C1', 'Oddíl/družina')
            ->setCellValue('D1', 'Typ akce')
            ->setCellValue('E1', 'Rozsah')
            ->setCellValue('F1', 'Místo konání')
            ->setCellValue('G1', 'Vedoucí akce')
            ->setCellValue('H1', 'Hospodář akce')
            ->setCellValue('I1', 'Od')
            ->setCellValue('J1', 'Do')
            ->setCellValue('K1', 'Počet dnů')
            ->setCellValue('L1', 'Počet účastníků')
            ->setCellValue('M1', 'Osobodnů')
            ->setCellValue('N1', 'Dětodnů')
            ->setCellValue('O1', $firstElement->parStatistic[1]->ParticipantCategory)
            ->setCellValue('P1', $firstElement->parStatistic[2]->ParticipantCategory)
            ->setCellValue('Q1', $firstElement->parStatistic[3]->ParticipantCategory)
            ->setCellValue('R1', $firstElement->parStatistic[4]->ParticipantCategory)
            ->setCellValue('S1', $firstElement->parStatistic[5]->ParticipantCategory)
            ->setCellValue('T1', 'Prefix');
        if ($allowPragueColumns) {
            $sheet->setCellValue('U1', 'Dotovatelná MHMP?')
                ->setCellValue('V1', 'Praž. osobodny pod 26')
                ->setCellValue('W1', 'Praž. uč. pod 18')
                ->setCellValue('X1', 'Praž. uč. mezi 18 a 26')
                ->setCellValue('Y1', 'Praž. uč. celkem');
            $sheet->getComment('U1')
                ->setWidth('200pt')->setHeight('50pt')->getText()
                ->createTextRun('Ověřte, zda jsou splněny další podmínky - např. akce konaná v době mimo školní vyučování (u táborů prázdnin), cílovou skupinou je studující mládež do 26 let.');
        }

        $rowCnt = 2;
        foreach ($data as $row) {
            /** @var Functions $functions */
            $functions  = $row->func;
            $leader     = $functions->getLeader() !== null ? $functions->getLeader()->getName() : null;
            $accountant = $functions->getAccountant() !== null ? $functions->getAccountant()->getName() : null;

            $sheet->setCellValue('A' . $rowCnt, $row->Unit)
                ->setCellValue('B' . $rowCnt, $row->DisplayName)
                ->setCellValue('C' . $rowCnt, $row->ID_UnitEducative !== null ? $row->UnitEducative : '')
                ->setCellValue('D' . $rowCnt, $row->EventGeneralType)
                ->setCellValue('E' . $rowCnt, $row->EventGeneralScope)
                ->setCellValue('F' . $rowCnt, $row->Location)
                ->setCellValue('G' . $rowCnt, $leader)
                ->setCellValue('H' . $rowCnt, $accountant)
                ->setCellValue('I' . $rowCnt, date('d.m.Y', strtotime($row->StartDate)))
                ->setCellValue('J' . $rowCnt, date('d.m.Y', strtotime($row->EndDate)))
                ->setCellValue('K' . $rowCnt, $row->TotalDays)
                ->setCellValue('L' . $rowCnt, $row->TotalParticipants)
                ->setCellValue('M' . $rowCnt, $row->PersonDays)
                ->setCellValue('N' . $rowCnt, $row->ChildDays)
                ->setCellValue('O' . $rowCnt, $row->parStatistic[1]->Count)
                ->setCellValue('P' . $rowCnt, $row->parStatistic[2]->Count)
                ->setCellValue('Q' . $rowCnt, $row->parStatistic[3]->Count)
                ->setCellValue('R' . $rowCnt, $row->parStatistic[4]->Count)
                ->setCellValue('S' . $rowCnt, $row->parStatistic[5]->Count)
                ->setCellValue('S' . $rowCnt, $row->prefix);
            if (isset($row->pragueParticipants)) {
                /** @var PragueParticipants $pp */
                $pp = $row->pragueParticipants;
                $sheet->setCellValue('U' . $rowCnt, $pp->isSupportable($row->TotalDays) ? 'Ano' : 'Ne')
                    ->setCellValue('V' . $rowCnt, $pp->getPersonDaysUnder26())
                    ->setCellValue('W' . $rowCnt, $pp->getUnder18())
                    ->setCellValue('X' . $rowCnt, $pp->getBetween18and26())
                    ->setCellValue('Y' . $rowCnt, $pp->getCitizensCount());
            }
            $rowCnt++;
        }
        $lastColumn = $allowPragueColumns ? 'W' : 'S';

        //format
        foreach (range('A', $lastColumn) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:' . $lastColumn . ($rowCnt - 1));
        $sheet->setTitle('Přehled akcí');
    }

    /**
     * @param mixed[] $data
     * @throws \PHPExcel_Exception
     */
    protected function setSheetCamps(\PHPExcel_Worksheet $sheet, array $data) : void
    {
        $firstElement = reset($data);

        $sheet->setCellValue('A1', 'Pořadatel')
            ->setCellValue('B1', 'Název akce')
            ->setCellValue('C1', 'Oddíly')
            ->setCellValue('D1', 'Místo konání')
            ->setCellValue('E1', 'Vedoucí akce')
            ->setCellValue('F1', 'Hospodář akce')
            ->setCellValue('G1', 'Od')
            ->setCellValue('H1', 'Do')
            ->setCellValue('I1', 'Počet dnů')
            ->setCellValue('J1', 'Počet účastníků')
            ->setCellValue('K1', 'Počet dospělých')
            ->setCellValue('L1', 'Počet dětí')
            ->setCellValue('M1', 'Osobodnů')
            ->setCellValue('N1', 'Dětodnů');

        $rowCnt = 2;
        foreach ($data as $row) {
            /** @var Functions $functions */
            $functions  = $row->func;
            $leader     = $functions->getLeader() !== null ? $functions->getLeader()->getName() : null;
            $accountant = $functions->getAccountant() !== null ? $functions->getAccountant()->getName() : null;

            $sheet->setCellValue('A' . $rowCnt, $row->Unit)
                ->setCellValue('B' . $rowCnt, $row->DisplayName)
                ->setCellValue('C' . $rowCnt, $row->troops)
                ->setCellValue('D' . $rowCnt, $row->Location)
                ->setCellValue('E' . $rowCnt, $leader)
                ->setCellValue('F' . $rowCnt, $accountant)
                ->setCellValue('G' . $rowCnt, date('d.m.Y', strtotime($row->StartDate)))
                ->setCellValue('H' . $rowCnt, date('d.m.Y', strtotime($row->EndDate)))
                ->setCellValue('I' . $rowCnt, $row->TotalDays)
                ->setCellValue('J' . $rowCnt, $row->RealCount)
                ->setCellValue('K' . $rowCnt, $row->RealAdult)
                ->setCellValue('L' . $rowCnt, $row->RealChild)
                ->setCellValue('M' . $rowCnt, $row->RealPersonDays)
                ->setCellValue('N' . $rowCnt, $row->RealChildDays);
            $rowCnt++;
        }

        //format
        foreach (range('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:N' . ($rowCnt - 1));
        $sheet->setTitle('Přehled táborů');
    }

    /**
     * @param mixed[] $data
     * @throws \PHPExcel_Exception
     */
    private function setSheetChits(\PHPExcel_Worksheet $sheet, array $data) : void
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
            /** @var CashbookId $cashbookId */
            $cashbookId = $event['cashbookId'];

            /** @var Cashbook $cashbook */
            $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));
            $prefix   = $cashbook->getChitNumberPrefix();

            /** @var string[] $categories */
            $categoryNames = $this->queryBus->handle(new CategoryPairsQuery($cashbookId, null));

            foreach ($event['chits'] as $chit) {
                /** @var Chit $chit */
                $isIncome = $chit->getCategory()->getOperationType()->equalsValue(Operation::INCOME);
                $amount   = $chit->getAmount()->toFloat();

                $sheet->setCellValue('A' . $rowCnt, $event->DisplayName)
                    ->setCellValue('B' . $rowCnt, $chit->getDate()->format('d.m.Y'))
                    ->setCellValue('C' . $rowCnt, $prefix . (string) $chit->getNumber())
                    ->setCellValue('D' . $rowCnt, $chit->getPurpose())
                    ->setCellValue('E' . $rowCnt, $categoryNames[$chit->getCategory()->getId()])
                    ->setCellValue('F' . $rowCnt, $chit->recipient)
                    ->setCellValue('G' . $rowCnt, $isIncome ? $amount : '')
                    ->setCellValue('H' . $rowCnt, ! $isIncome ? $amount : '');

                $rowCnt++;
            }
        }

        //format
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:H' . ($rowCnt - 1));
        $sheet->setTitle('Doklady');
    }

    /**
     * @param Chit[] $chits
     * @throws \PHPExcel_Exception
     */
    private function setSheetChitsOnly(\PHPExcel_Worksheet $sheet, array $chits, CashbookId $cashbookId) : void
    {
        $sheet->setCellValue('B1', 'Ze dne')
            ->setCellValue('C1', 'Účel výplaty')
            ->setCellValue('D1', 'Kategorie')
            ->setCellValue('E1', 'Komu/Od')
            ->setCellValue('F1', 'Částka')
            ->setCellValue('G1', 'Typ');

        $rowCnt = 2;
        $sumIn  = $sumOut = 0;

        /** @var string[] $categoryNames */
        $categoryNames = $this->queryBus->handle(new CategoryPairsQuery($cashbookId));

        foreach ($chits as $chit) {
            $amount = $chit->getAmount()->toFloat();

            $sheet->setCellValue('A' . $rowCnt, $rowCnt - 1)
                ->setCellValue('B' . $rowCnt, $chit->getDate()->format('d.m.Y'))
                ->setCellValue('C' . $rowCnt, $chit->getPurpose())
                ->setCellValue('D' . $rowCnt, $categoryNames[$chit->getCategory()->getId()])
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
        $sheet->getStyle('A1:G' . ($rowCnt - 1))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

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
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->setTitle('Doklady');
    }

    protected function send(\PHPExcel $obj, string $filename) : void
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

        $objWriter = new \PHPExcel_Writer_Excel2007($obj);
        $objWriter->setPreCalculateFormulas(true);
        $objWriter->save('php://output');
        //exit;
    }
}
