<?php

namespace Model;

class ExcelService extends BaseService {

    protected function getNewFile() {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()
                ->setCreator("h.skauting.cz")
                ->setLastModifiedBy("h.skauting.cz")
//                ->setTitle("Office 2007 XLSX Test Document")
//                ->setSubject("Office 2007 XLSX Test Document")
//                ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
//                ->setKeywords("office 2007 openxml php")
//                ->setCategory("Test result file")
        ;
        return $objPHPExcel;
    }

    public function getParticipants(EventEntity $service, $event, $type = "generalEvent") {
        $objPHPExcel = $this->getNewFile();
        $data = $service->participants->getAll($event->ID, TRUE);
        $sheet = $objPHPExcel->getActiveSheet();
        if ($type == "camp") {
            $this->setSheetParticipantCamp($sheet, $data);
        } else {//GENERAL EVENT
            $this->setSheetParticipantGeneral($sheet, $data, $event);
        }
        $this->send($objPHPExcel, \Nette\Utils\Strings::webalize($event->DisplayName) . "-" . date("Y_n_j"));
    }

    /**
     * 
     * @param EventService $service
     * @param type $aid číslo akce
     * @param type $event 
     */
    public function getCashbook(EventEntity $service, $event) {
        $objPHPExcel = $this->getNewFile();
        $data = $service->chits->getAll($event->ID);
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetCashbook($sheet, $data, $event->prefix);
        $this->send($objPHPExcel, \Nette\Utils\Strings::webalize($event->DisplayName) . "-pokladni-kniha-" . date("Y_n_j"));
    }

    public function getEventSummaries($eventIds, EventEntity $service) {
        $objPHPExcel = $this->getNewFile();

        $data = array();
        foreach ($eventIds as $aid) {
            $data[$aid] = $service->event->get($aid);
            $data[$aid]['parStatistic'] = $service->participants->getEventStatistic($aid);
            $data[$aid]['chits'] = $service->chits->getAll($aid);
            $data[$aid]['func'] = $service->event->getFunctions($aid);
            $participants = $service->participants->getAll($aid);
            $data[$aid]['participantsCnt'] = count($participants);
            $data[$aid]['personDays'] = $service->participants->getPersonsDays($participants);
        }
        $sheetEvents = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetEvents($sheetEvents, $data);
        $objPHPExcel->createSheet(1);
        $sheetChit = $objPHPExcel->setActiveSheetIndex(1);
        $this->setSheetChits($sheetChit, $data);
        $this->send($objPHPExcel, "Souhrn-akcí-" . date("Y_n_j"));
    }

    public function getChitsExport($chits) {
        $objPHPExcel = $this->getNewFile();
        $sheetChit = $objPHPExcel->setActiveSheetIndex(0);
        $this->setSheetChitsOnly($sheetChit, $chits);
        $this->send($objPHPExcel, "Export-vybranych-paragonu");
    }

    /* PROTECTED */

    protected function setSheetParticipantCamp(&$sheet, $data) {
        $sheet->setCellValue('A1', "P.č.")
                ->setCellValue('B1', "Jméno")
                ->setCellValue('C1', "Příjmení")
                ->setCellValue('D1', "Ulice")
                ->setCellValue('E1', "Město")
                ->setCellValue('F1', "PSČ")
                ->setCellValue('G1', "Datum narození")
                ->setCellValue('H1', "Osobodny")
                ->setCellValue('I1', "Dětodny")
                ->setCellValue('J1', "Zaplaceno")
                ->setCellValue('K1', "Vratka")
                ->setCellValue('L1', "Celkem")
                ->setCellValue('M1', "Na účet");

        $rowCnt = 2;

        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowCnt, ($rowCnt - 1))
                    ->setCellValue('B' . $rowCnt, $row->FirstName)
                    ->setCellValue('C' . $rowCnt, $row->LastName)
                    ->setCellValue('D' . $rowCnt, $row->Street)
                    ->setCellValue('E' . $rowCnt, $row->City)
                    ->setCellValue('F' . $rowCnt, $row->Postcode)
                    ->setCellValue('G' . $rowCnt, !is_null($row->Birthday) ? date("d.m.Y", strtotime($row->Birthday)) : "")
                    ->setCellValue('H' . $rowCnt, $row->Days)
                    ->setCellValue('I' . $rowCnt, $row->Age < self::ADULT_AGE ? $row->Days : 0)
                    ->setCellValue('J' . $rowCnt, !is_null($row->payment) ? $row->payment : 0)
                    ->setCellValue('K' . $rowCnt, !is_null($row->repayment) ? $row->repayment : 0)
                    ->setCellValue('L' . $rowCnt, ($row->payment - $row->repayment))
                    ->setCellValue('M' . $rowCnt, $row->isAccount == "Y" ? "Ano" : "Ne");
            $rowCnt++;
        }
        //format
        foreach (range('A', 'M') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:M' . ($rowCnt - 1));
    }

    protected function setSheetParticipantGeneral(&$sheet, $data, $event) {
        $startDate = new \DateTime($event->StartDate);
        $sheet->setCellValue('A1', "P.č.")
                ->setCellValue('B1', "Jméno")
                ->setCellValue('C1', "Příjmení")
                ->setCellValue('D1', "Ulice")
                ->setCellValue('E1', "Město")
                ->setCellValue('F1', "PSČ")
                ->setCellValue('G1', "Datum narození")
                ->setCellValue('H1', "Osobodny")
                ->setCellValue('I1', "Dětodny");

        $rowCnt = 2;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowCnt, ($rowCnt - 1))
                    ->setCellValue('B' . $rowCnt, $row->FirstName)
                    ->setCellValue('C' . $rowCnt, $row->LastName)
                    ->setCellValue('D' . $rowCnt, $row->Street)
                    ->setCellValue('E' . $rowCnt, $row->City)
                    ->setCellValue('F' . $rowCnt, $row->Postcode)
                    ->setCellValue('G' . $rowCnt, !is_null($row->Birthday) ? date("d.m.Y", strtotime($row->Birthday)) : "")
                    ->setCellValue('H' . $rowCnt, $row->Days)
                    ->setCellValue('I' . $rowCnt, ($startDate->diff(new \DateTime($row->Birthday))->format('%y') < self::ADULT_AGE && !is_null($row->Birthday)) ? $row->Days : 0)
            ;
            $rowCnt++;
        }
        //format
        foreach (range('A', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:I' . ($rowCnt - 1));
        $sheet->setTitle('Seznam účastníků');
    }

    protected function setSheetCashbook(&$sheet, $data, $prefix) {
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
//                        <tr{if $balance < 0} class="alert alert-error"{/if}>
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
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('H1:H' . ($rowCnt - 1))->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:H' . ($rowCnt - 1));

        $sheet->setTitle('Pokladní kniha');
    }

    protected function setSheetEvents(&$sheet, $data) {
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
                ->setCellValue('S1', $firstElement->parStatistic[5]->ParticipantCategory)
        ;

        $rowCnt = 2;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $rowCnt, $row->Unit)
                    ->setCellValue('B' . $rowCnt, $row->DisplayName)
                    ->setCellValue('C' . $rowCnt, $row->ID_UnitEducative !== NULL ? $row->UnitEducative : "")
                    ->setCellValue('D' . $rowCnt, $row->EventGeneralType)
                    ->setCellValue('E' . $rowCnt, $row->EventGeneralScope)
                    ->setCellValue('F' . $rowCnt, $row->Location)
                    ->setCellValue('G' . $rowCnt, $row->func[0]->ID_Person !== NULL ? $row->func[0]->Person : "")
                    ->setCellValue('H' . $rowCnt, $row->func[2]->ID_Person !== NULL ? $row->func[2]->Person : "")
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
                    ->setCellValue('S' . $rowCnt, $row->parStatistic[5]->Count)
            ;
            $rowCnt++;
        }

        //format
        foreach (range('A', 'S') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:S1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:S' . ($rowCnt - 1));
        $sheet->setTitle('Přehled akcí');
    }

    protected function setSheetChits(&$sheet, $data) {
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
                        ->setCellValue('C' . $rowCnt, $event->prefix !== NULL ? $event->prefix . $chit->num : "")
                        ->setCellValue('D' . $rowCnt, $chit->purpose)
                        ->setCellValue('E' . $rowCnt, $chit->clabel)
                        ->setCellValue('F' . $rowCnt, $chit->recipient)
                        ->setCellValue('G' . $rowCnt, $chit->ctype == "in" ? $chit->price : "")
                        ->setCellValue('H' . $rowCnt, $chit->ctype != "in" ? $chit->price : "")
                ;
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

    protected function setSheetChitsOnly(&$sheet, $data) {
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
                    ->setCellValue('G' . $rowCnt, $chit->ctype == "in" ? "Příjem" : "Výdaj")
            ;
            if ($chit->ctype == "in") {
                $sumIn += $chit->price;
            } else {
                $sumOut += $chit->price;
            }
            $rowCnt++;
        }
        //add border
        $sheet->getStyle('A1:G'.($rowCnt- 1))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        
        if ($sumIn > 0) {
            $rowCnt++;
            $sheet->setCellValue('E' . $rowCnt, "Příjmy")
                    ->setCellValue('F' . $rowCnt, $sumIn);
            $sheet->getStyle('E' . $rowCnt)->getFont()->setBold(true);
        }
        if ($sumOut > 0) {
            $rowCnt++;
            $sheet->setCellValue('E' . $rowCnt, "Výdaje")
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

    protected function send(\PHPExcel $obj, $filename) {
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

        $objWriter = \PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        $objWriter->save('php://output');
        //exit;
    }

}
