<?php

namespace Model;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Repositories\CategoryRepository;
use Model\Cashbook\Repositories\ICashbookRepository;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Excel\Builders\CashbookWithCategoriesBuilder;
use Nette\Utils\Strings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelService
{

    private const ADULT_AGE = 18;

    /** @var CategoryRepository */
    private $categories;

    /** @var ICashbookRepository */
    private $cashbooks;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(CategoryRepository $categories, ICashbookRepository $cashbooks, QueryBus $queryBus)
    {
        $this->categories = $categories;
        $this->cashbooks = $cashbooks;
        $this->queryBus = $queryBus;
    }

    protected function getNewFile(): \PHPExcel
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("h.skauting.cz")
            ->setLastModifiedBy("h.skauting.cz");

        return $objPHPExcel;
    }

    private function getNewFileV2(): Spreadsheet
    {
        $sheet = new Spreadsheet();
        $sheet->getProperties()
            ->setCreator('h.skauting.cz')
            ->setLastModifiedBy('h.skauting.cz');

        return $sheet;
    }

    public function getParticipants(EventEntity $service, $event, $type = "general"): void
    {
        $objPHPExcel = $this->getNewFile();
        $data = $service->participants->getAll($event->ID);
        $sheet = $objPHPExcel->getActiveSheet();
        if ($type == "camp") {
            $this->setSheetParticipantCamp($sheet, $data);
        } else {//GENERAL EVENT
            $this->setSheetParticipantGeneral($sheet, $data, $event);
        }
        $this->send($objPHPExcel, \Nette\Utils\Strings::webalize($event->DisplayName) . "-" . date("Y_n_j"));
    }

    /**
     * @param EventEntity $service
     * @param \stdClass $event
     */
    public function getCashbook(EventEntity $service, $event): void
    {
        $objPHPExcel = $this->getNewFile();
        $data = $service->chits->getAll($event->ID);
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetCashbook($sheet, $data, $event->prefix);
        $this->send($objPHPExcel, \Nette\Utils\Strings::webalize($event->DisplayName) . "-pokladni-kniha-" . date("Y_n_j"));
    }

    public function getCashbookWithCategories(EventEntity $eventEntity, int $eventId): void
    {
        $excel = $this->getNewFileV2();
        $sheet = $excel->getActiveSheet();

        $builder = new CashbookWithCategoriesBuilder($this->categories, $this->cashbooks);
        $builder->build($sheet, $eventEntity, $eventId);

        $this->sendV2($excel, 'test');
    }

    public function getEventSummaries(array $eventIds, EventEntity $service): void
    {
        $objPHPExcel = $this->getNewFile();

        $allowPragueColumns = false;
        $data = [];
        foreach ($eventIds as $aid) {
            $data[$aid] = $service->event->get($aid);
            $data[$aid]['parStatistic'] = $service->participants->getEventStatistic($aid);
            $data[$aid]['chits'] = $service->chits->getAll($aid);
            $data[$aid]['func'] = $this->queryBus->handle(new EventFunctions(new SkautisEventId($aid)));
            $participants = $service->participants->getAll($aid);
            $data[$aid]['participantsCnt'] = count($participants);
            $data[$aid]['personDays'] = $service->participants->getPersonsDays($participants);
            $pp = $service->participants->countPragueParticipants($data[$aid]);
            if ($pp !== NULL) { //Prague event
                $allowPragueColumns = true;
                $pp["isSupportable"] = $pp["underAge"] >= 8 && $data[$aid]->TotalDays >= 2 && $data[$aid]->TotalDays <= 6;
                $data[$aid]["prague"] = $pp;
            }
        }
        $sheetEvents = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetEvents($sheetEvents, $data, $allowPragueColumns);
        $objPHPExcel->createSheet(1);
        $sheetChit = $objPHPExcel->setActiveSheetIndex(1);
        $this->setSheetChits($sheetChit, $data);
        $this->send($objPHPExcel, "Souhrn-akcí-" . date("Y_n_j"));
    }

    public function getCampsSummary(array $campsIds, EventEntity $service, UnitService $unitService): void
    {
        $objPHPExcel = $this->getNewFile();

        $data = [];
        foreach ($campsIds as $aid) {
            $camp = $service->event->get($aid);
            $data[$aid] = $camp;
            $data[$aid]['troops'] = implode(', ', $unitService->getCampTroopNames($camp));
            $data[$aid]['chits'] = $service->chits->getAll($aid);
            $data[$aid]['func'] = $this->queryBus->handle(new CampFunctions(new SkautisCampId($aid)));
            $participants = $service->participants->getAll($aid);
            $data[$aid]['participantsCnt'] = count($participants);
            $data[$aid]['personDays'] = $service->participants->getPersonsDays($participants);
        }
        $sheetCamps = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetCamps($sheetCamps, $data);
        $objPHPExcel->createSheet(1);
        $sheetChit = $objPHPExcel->setActiveSheetIndex(1);
        $this->setSheetChits($sheetChit, $data);
        $this->send($objPHPExcel, "Souhrn-táborů-" . date("Y_n_j"));
    }

    public function getChitsExport($chits): void
    {
        $objPHPExcel = $this->getNewFile();
        $sheetChit = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetChitsOnly($sheetChit, $chits);
        $this->send($objPHPExcel, "Export-vybranych-paragonu");
    }

    /* PROTECTED */

    protected function setSheetParticipantCamp(\PHPExcel_Worksheet $sheet, $data): void
    {
        $sheet->setCellValue('A1', "P.č.")
            ->setCellValue('B1', "Jméno")
            ->setCellValue('C1', "Příjmení")
            ->setCellValue('D1', "Příjmení")
            ->setCellValue('E1', "Ulice")
            ->setCellValue('F1', "Město")
            ->setCellValue('G1', "PSČ")
            ->setCellValue('H1', "Datum narození")
            ->setCellValue('I1', "Osobodny")
            ->setCellValue('J1', "Dětodny")
            ->setCellValue('K1', "Zaplaceno")
            ->setCellValue('L1', "Vratka")
            ->setCellValue('M1', "Celkem")
            ->setCellValue('N1', "Na účet");

        $rowCnt = 2;

        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowCnt, ($rowCnt - 1))
                ->setCellValue('B' . $rowCnt, $row->FirstName)
                ->setCellValue('C' . $rowCnt, $row->LastName)
                ->setCellValue('D' . $rowCnt, $row->NickName)
                ->setCellValue('E' . $rowCnt, $row->Street)
                ->setCellValue('F' . $rowCnt, $row->City)
                ->setCellValue('G' . $rowCnt, $row->Postcode)
                ->setCellValue('H' . $rowCnt, !is_null($row->Birthday) ? date("d.m.Y", strtotime($row->Birthday)) : "")
                ->setCellValue('I' . $rowCnt, $row->Days)
                ->setCellValue('J' . $rowCnt, $row->Age < self::ADULT_AGE ? $row->Days : 0)
                ->setCellValue('K' . $rowCnt, !is_null($row->payment) ? $row->payment : 0)
                ->setCellValue('L' . $rowCnt, !is_null($row->repayment) ? $row->repayment : 0)
                ->setCellValue('M' . $rowCnt, ($row->payment - $row->repayment))
                ->setCellValue('N' . $rowCnt, $row->isAccount == "Y" ? "Ano" : "Ne");
            $rowCnt++;
        }
        //format
        foreach (range('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(TRUE);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(TRUE);
        $sheet->setAutoFilter('A1:N' . ($rowCnt - 1));
    }

    protected function setSheetParticipantGeneral(\PHPExcel_Worksheet $sheet, $data, $event): void
    {
        $startDate = new \DateTime($event->StartDate);
        $sheet->setCellValue('A1', "P.č.")
            ->setCellValue('B1', "Jméno")
            ->setCellValue('C1', "Příjmení")
            ->setCellValue('D1', "Přezdívka")
            ->setCellValue('E1', "Ulice")
            ->setCellValue('F1', "Město")
            ->setCellValue('G1', "PSČ")
            ->setCellValue('H1', "Datum narození")
            ->setCellValue('I1', "Osobodny")
            ->setCellValue('J1', "Dětodny")
            ->setCellValue('K1', "Zaplaceno");

        $rowCnt = 2;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowCnt, ($rowCnt - 1))
                ->setCellValue('B' . $rowCnt, $row->FirstName)
                ->setCellValue('C' . $rowCnt, $row->LastName)
                ->setCellValue('D' . $rowCnt, $row->NickName)
                ->setCellValue('E' . $rowCnt, $row->Street)
                ->setCellValue('F' . $rowCnt, $row->City)
                ->setCellValue('G' . $rowCnt, $row->Postcode)
                ->setCellValue('H' . $rowCnt, !is_null($row->Birthday) ? date("d.m.Y", strtotime($row->Birthday)) : "")
                ->setCellValue('I' . $rowCnt, $row->Days)
                ->setCellValue('J' . $rowCnt, ($startDate->diff(new \DateTime($row->Birthday))->format('%y') < self::ADULT_AGE && !is_null($row->Birthday)) ? $row->Days : 0)
                ->setCellValue('K' . $rowCnt, $row->payment);
            $rowCnt++;
        }
        //format
        foreach (range('A', 'J') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(TRUE);
        }
        $sheet->getStyle('A1:J1')->getFont()->setBold(TRUE);
        $sheet->setAutoFilter('A1:J' . ($rowCnt - 1));
        $sheet->setTitle('Seznam účastníků');
    }

    protected function setSheetCashbook(\PHPExcel_Worksheet $sheet, $data, $prefix): void
    {
        $sheet->setCellValue('A1', "Ze dne")
            ->setCellValue('B1', "Číslo dokladu")
            ->setCellValue('C1', "Účel platby")
            ->setCellValue('D1', "Kategorie")
            ->setCellValue('E1', "Komu/od")
            ->setCellValue('F1', "Příjem")
            ->setCellValue('G1', "Výdej")
            ->setCellValue('H1', "Zůstatek");

        $balance = 0;
        $rowCnt = 2;
        foreach ($data as $row) {
            $balance += $row->ctype == 'in' ? $row->price : (-$row->price);
            $sheet->setCellValue('A' . $rowCnt, date("d.m.Y", strtotime($row->date)))
                ->setCellValue('B' . $rowCnt, $prefix . $row->num)
                ->setCellValue('C' . $rowCnt, $row->purpose)
                ->setCellValue('D' . $rowCnt, $row->clabel)
                ->setCellValue('E' . $rowCnt, $row->recipient)
                ->setCellValue('F' . $rowCnt, $row->ctype == 'in' ? $row->price : "")
                ->setCellValue('G' . $rowCnt, $row->ctype != 'in' ? $row->price : "")
                ->setCellValue('H' . $rowCnt, $balance);
            $rowCnt++;
        }

        //format
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(TRUE);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(TRUE);
        $sheet->getStyle('H1:H' . ($rowCnt - 1))->getFont()->setBold(TRUE);
        $sheet->setAutoFilter('A1:H' . ($rowCnt - 1));

        $sheet->setTitle('Pokladní kniha');
    }

    protected function setSheetEvents(\PHPExcel_Worksheet $sheet, $data, bool $allowPragueColumns = false): void
    {
        $firstElement = reset($data);

        $sheet->setCellValue('A1', "Pořadatel")
            ->setCellValue('B1', "Název akce")
            ->setCellValue('C1', "Oddíl/družina")
            ->setCellValue('D1', "Typ akce")
            ->setCellValue('E1', "Rozsah")
            ->setCellValue('F1', "Místo konání")
            ->setCellValue('G1', "Vedoucí akce")
            ->setCellValue('H1', "Hospodář akce")
            ->setCellValue('I1', "Od")
            ->setCellValue('J1', "Do")
            ->setCellValue('K1', "Počet dnů")
            ->setCellValue('L1', "Počet účastníků")
            ->setCellValue('M1', "Osobodnů")
            ->setCellValue('N1', "Dětodnů")
            ->setCellValue('O1', $firstElement->parStatistic[1]->ParticipantCategory)
            ->setCellValue('P1', $firstElement->parStatistic[2]->ParticipantCategory)
            ->setCellValue('Q1', $firstElement->parStatistic[3]->ParticipantCategory)
            ->setCellValue('R1', $firstElement->parStatistic[4]->ParticipantCategory)
            ->setCellValue('S1', $firstElement->parStatistic[5]->ParticipantCategory);
        if ($allowPragueColumns) {
            $sheet->setCellValue('T1', "Dotovatelná MHMP?")
                ->setCellValue('U1', "Praž. uč. pod " . $firstElement->prague['ageThreshold'])
                ->setCellValue('V1', "Praž. uč. celkem");
            $sheet->getComment('T1')
                ->setWidth("200pt")->setHeight("50pt")->getText()
                ->createTextRun('Ověřte, zda jsou splněny další podmínky - např. akce konaná v době mimo školní vyučování (u táborů prázdnin), cílovou skupinou je studující mládež do 26 let.');
        }

        $rowCnt = 2;
        foreach ($data as $row) {
            /** @var Functions $functions */
            $functions = $row->func;
            $leader = $functions->getLeader() !== NULL ? $functions->getLeader()->getName() : NULL;
            $accountant = $functions->getAccountant() !== NULL ? $functions->getAccountant()->getName() : NULL;

            $sheet->setCellValue('A' . $rowCnt, $row->Unit)
                ->setCellValue('B' . $rowCnt, $row->DisplayName)
                ->setCellValue('C' . $rowCnt, $row->ID_UnitEducative !== NULL ? $row->UnitEducative : "")
                ->setCellValue('D' . $rowCnt, $row->EventGeneralType)
                ->setCellValue('E' . $rowCnt, $row->EventGeneralScope)
                ->setCellValue('F' . $rowCnt, $row->Location)
                ->setCellValue('G' . $rowCnt, $leader)
                ->setCellValue('H' . $rowCnt, $accountant)
                ->setCellValue('I' . $rowCnt, date("d.m.Y", strtotime($row->StartDate)))
                ->setCellValue('J' . $rowCnt, date("d.m.Y", strtotime($row->EndDate)))
                ->setCellValue('K' . $rowCnt, $row->TotalDays)
                ->setCellValue('L' . $rowCnt, $row->TotalParticipants)
                ->setCellValue('M' . $rowCnt, $row->PersonDays)
                ->setCellValue('N' . $rowCnt, $row->ChildDays)
                ->setCellValue('O' . $rowCnt, $row->parStatistic[1]->Count)
                ->setCellValue('P' . $rowCnt, $row->parStatistic[2]->Count)
                ->setCellValue('Q' . $rowCnt, $row->parStatistic[3]->Count)
                ->setCellValue('R' . $rowCnt, $row->parStatistic[4]->Count)
                ->setCellValue('S' . $rowCnt, $row->parStatistic[5]->Count);
            if (isset($row->prague)) {
                $sheet->setCellValue('T' . $rowCnt, $row->prague['isSupportable'] ? "Ano" : "Ne")
                    ->setCellValue('U' . $rowCnt, $row->prague['underAge'])
                    ->setCellValue('V' . $rowCnt, $row->prague['all']);
            }
            $rowCnt++;
        }

        //format
        foreach (range('A', 'V') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(TRUE);
        }
        $sheet->getStyle('A1:V1')->getFont()->setBold(TRUE);
        $sheet->setAutoFilter('A1:V' . ($rowCnt - 1));
        $sheet->setTitle('Přehled akcí');
    }

    protected function setSheetCamps(\PHPExcel_Worksheet $sheet, array $data): void
    {
        $firstElement = reset($data);

        $sheet->setCellValue('A1', "Pořadatel")
            ->setCellValue('B1', "Název akce")
            ->setCellValue('C1', "Oddíly")
            ->setCellValue('D1', "Místo konání")
            ->setCellValue('E1', "Vedoucí akce")
            ->setCellValue('F1', "Hospodář akce")
            ->setCellValue('G1', "Od")
            ->setCellValue('H1', "Do")
            ->setCellValue('I1', "Počet dnů")
            ->setCellValue('J1', "Počet účastníků")
            ->setCellValue('K1', "Počet dospělých")
            ->setCellValue('L1', "Počet dětí")
            ->setCellValue('M1', "Osobodnů")
            ->setCellValue('N1', "Dětodnů");

        $rowCnt = 2;
        foreach ($data as $row) {
            /** @var Functions $functions */
            $functions = $row->func;
            $leader = $functions->getLeader() !== NULL ? $functions->getLeader()->getName() : NULL;
            $accountant = $functions->getAccountant() !== NULL ? $functions->getAccountant()->getName() : NULL;

            $sheet->setCellValue('A' . $rowCnt, $row->Unit)
                ->setCellValue('B' . $rowCnt, $row->DisplayName)
                ->setCellValue('C' . $rowCnt, $row->troops)
                ->setCellValue('D' . $rowCnt, $row->Location)
                ->setCellValue('E' . $rowCnt, $leader)
                ->setCellValue('F' . $rowCnt, $accountant)
                ->setCellValue('G' . $rowCnt, date("d.m.Y", strtotime($row->StartDate)))
                ->setCellValue('H' . $rowCnt, date("d.m.Y", strtotime($row->EndDate)))
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
            $sheet->getColumnDimension($columnID)->setAutoSize(TRUE);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(TRUE);
        $sheet->setAutoFilter('A1:N' . ($rowCnt - 1));
        $sheet->setTitle('Přehled táborů');
    }

    protected function setSheetChits(\PHPExcel_Worksheet $sheet, $data): void
    {
        $sheet->setCellValue('A1', "Název akce")
            ->setCellValue('B1', "Ze dne")
            ->setCellValue('C1', "Číslo dokladu")
            ->setCellValue('D1', "Účel výplaty")
            ->setCellValue('E1', "Kategorie")
            ->setCellValue('F1', "Komu/Od")
            ->setCellValue('G1', "Příjem")
            ->setCellValue('H1', "Výdej");

        $rowCnt = 2;
        foreach ($data as $event) {
            foreach ($event['chits'] as $chit) {
                $sheet->setCellValue('A' . $rowCnt, $event->DisplayName)
                    ->setCellValue('B' . $rowCnt, date("d.m.Y", strtotime($chit->date)))
                    ->setCellValue('C' . $rowCnt, $event->prefix . $chit->num)
                    ->setCellValue('D' . $rowCnt, $chit->purpose)
                    ->setCellValue('E' . $rowCnt, $chit->clabel)
                    ->setCellValue('F' . $rowCnt, $chit->recipient)
                    ->setCellValue('G' . $rowCnt, $chit->ctype == "in" ? $chit->price : "")
                    ->setCellValue('H' . $rowCnt, $chit->ctype != "in" ? $chit->price : "");
                $rowCnt++;
            }
        }

        //format
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(TRUE);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(TRUE);
        $sheet->setAutoFilter('A1:H' . ($rowCnt - 1));
        $sheet->setTitle('Doklady');
    }

    protected function setSheetChitsOnly(\PHPExcel_Worksheet $sheet, $data): void
    {
        $sheet->setCellValue('B1', "Ze dne")
            ->setCellValue('C1', "Účel výplaty")
            ->setCellValue('D1', "Kategorie")
            ->setCellValue('E1', "Komu/Od")
            ->setCellValue('F1', "Částka")
            ->setCellValue('G1', "Typ");

        $rowCnt = 2;
        $sumIn = $sumOut = 0;

        foreach ($data as $chit) {
            $sheet->setCellValue('A' . $rowCnt, $rowCnt - 1)
                ->setCellValue('B' . $rowCnt, date("d.m.Y", strtotime($chit->date)))
                ->setCellValue('C' . $rowCnt, $chit->purpose)
                ->setCellValue('D' . $rowCnt, $chit->clabel)
                ->setCellValue('E' . $rowCnt, $chit->recipient)
                ->setCellValue('F' . $rowCnt, $chit->price)
                ->setCellValue('G' . $rowCnt, $chit->ctype == "in" ? "Příjem" : "Výdaj");
            if ($chit->ctype == "in") {
                $sumIn += $chit->price;
            } else {
                $sumOut += $chit->price;
            }
            $rowCnt++;
        }
        //add border
        $sheet->getStyle('A1:G' . ($rowCnt - 1))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        if ($sumIn > 0) {
            $rowCnt++;
            $sheet->setCellValue('E' . $rowCnt, "Příjmy")
                ->setCellValue('F' . $rowCnt, $sumIn);
            $sheet->getStyle('E' . $rowCnt)->getFont()->setBold(TRUE);
        }
        if ($sumOut > 0) {
            $rowCnt++;
            $sheet->setCellValue('E' . $rowCnt, "Výdaje")
                ->setCellValue('F' . $rowCnt, $sumOut);
            $sheet->getStyle('E' . $rowCnt)->getFont()->setBold(TRUE);
        }

        //format
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(TRUE);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(TRUE);
        $sheet->setTitle('Doklady');
    }

    protected function send(\PHPExcel $obj, $filename): void
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
        $objWriter->setPreCalculateFormulas(TRUE);
        $objWriter->save('php://output');
        //exit;
    }

    private function sendV2(Spreadsheet $sheet, string $filename): void
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

        $xls = new Xlsx($sheet);
        $xls->setPreCalculateFormulas(TRUE);
        $xls->save('php://output');
        //exit;
    }

}
