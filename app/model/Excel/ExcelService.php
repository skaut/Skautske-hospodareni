<?php

class ExcelService extends BaseService {

    protected function getNewFile() {
        $objPHPExcel = new PHPExcel();
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

        if ($type == "camp") {
            $data = $service->participants->getAllPersonDetail($event->ID, $service->participants->getAllWithDetails($event->ID));
            $this->setSheetParticipantCamp($objPHPExcel->getActiveSheet(), $data);
        } else {//GENERAL EVENT
            $data = $service->participants->getAllPersonDetail($event->ID, $service->participants->getAll($event->ID));
            $this->setSheetParticipantGeneral($objPHPExcel->getActiveSheet(), $data, $event);
        }
        $this->send($objPHPExcel, Nette\Utils\Strings::webalize($event->DisplayName) . "-v" . date("Y_n_j"));
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
        $this->setSheetCashbook($objPHPExcel->setActiveSheetIndex(0), $data, $event->prefix);
        $this->send($objPHPExcel, Nette\Utils\Strings::webalize($event->DisplayName) . "-pokladni-kniha-" . date("Y_n_j"));
    }

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
                    ->setCellValue('I' . $rowCnt, $row->Age < ADULT_AGE ? $row->Days : 0)
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
        $startDate = new DateTime($event->StartDate);
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
                    ->setCellValue('I' . $rowCnt, ($startDate->diff(new DateTime($row->Birthday))->format('%y') < self::ADULT_AGE && !is_null($row->Birthday)) ? $row->Days : 0)
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
                ->setCellValue('E1', "Příjem")
                ->setCellValue('F1', "Výdej")
                ->setCellValue('G1', "Zůstatek");

        $balance = 0;
        $rowCnt = 2;
        foreach ($data as $row) {
            $balance += $row->ctype == 'in' ? $row->price : (-$row->price);
//                        <tr{if $balance < 0} class="alert alert-error"{/if}>
            $sheet->setCellValue('A' . $rowCnt, date("d.m.Y", strtotime($row->date)))
                    ->setCellValue('B' . $rowCnt, $prefix . $row->num)
                    ->setCellValue('C' . $rowCnt, $row->purpose)
                    ->setCellValue('D' . $rowCnt, $row->clabel)
                    ->setCellValue('E' . $rowCnt, $row->ctype == 'in' ? $row->price : "")
                    ->setCellValue('F' . $rowCnt, $row->ctype != 'in' ? $row->price : "")
                    ->setCellValue('G' . $rowCnt, $balance);
            $rowCnt++;
        }

        //format
        foreach (range('A', 'G') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('G1:G' . ($rowCnt - 1))->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:G' . ($rowCnt - 1));

        $sheet->setTitle('Pokladní kniha');
    }

    protected function send(PHPExcel $obj, $filename) {
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

        $objWriter = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        $objWriter->save('php://output');
        //exit;
    }

}
