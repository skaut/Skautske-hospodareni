<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers\Pdf;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportEvents;
use Model\Cashbook\ReadModel\SpreadsheetFactory;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\SkautisEventId;
use Model\Excel\Range;
use Model\IEventServiceFactory;
use Model\IParticipantServiceFactory;
use Model\Participant\PragueParticipants;
use Nette\Utils\ArrayHash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use function array_key_first;
use function assert;
use function count;
use function date;
use function strtotime;
use function ucfirst;

class ExportEventsHandler
{
    /** @var IParticipantServiceFactory */
    private $participantServiceFactory;

    /** @var IEventServiceFactory */
    private $serviceFactory;

    /** @var QueryBus */
    private $queryBus;

    /** @var SpreadsheetFactory */
    private $spreadsheetFactory;

    public function __construct(
        IParticipantServiceFactory $participantServiceFactory,
        IEventServiceFactory $serviceFactory,
        QueryBus $queryBus,
        SpreadsheetFactory $spreadsheetFactory
    ) {
        $this->participantServiceFactory = $participantServiceFactory;
        $this->serviceFactory            = $serviceFactory;
        $this->queryBus                  = $queryBus;
        $this->spreadsheetFactory        = $spreadsheetFactory;
    }

    public function __invoke(ExportEvents $query) : Spreadsheet
    {
        $eventService       = $this->serviceFactory->create(ucfirst(ObjectType::EVENT));
        $participantService = $this->participantServiceFactory->create(ucfirst(ObjectType::EVENT));
        $spreadsheet        = $this->spreadsheetFactory->create();

        $allowPragueColumns = false;
        $data               = [];
        foreach ($query->getEventIds() as $aid) {
            $eventId    = new SkautisEventId($aid);
            $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery($eventId));

            $data[$aid]                    = $eventService->get($aid);
            $data[$aid]['cashbookId']      = $cashbookId;
            $data[$aid]['parStatistic']    = $participantService->getEventStatistic($aid);
            $data[$aid]['chits']           = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId));
            $data[$aid]['func']            = $this->queryBus->handle(new EventFunctions($eventId));
            $participants                  = $participantService->getAll($aid);
            $data[$aid]['participantsCnt'] = count($participants);
            $data[$aid]['personDays']      = $participantService->getPersonsDays($participants);
            $pp                            = $participantService->countPragueParticipants($data[$aid]);
            if ($pp === null) {
                continue;
            }
            //Prague event
            $allowPragueColumns               = true;
            $data[$aid]['pragueParticipants'] = $pp;
        }
        $sheetEvents = $spreadsheet->setActiveSheetIndex(0);
        $this->setSheetEvents($sheetEvents, $data, $allowPragueColumns);
        $spreadsheet->createSheet(1);
        $sheetChit = $spreadsheet->setActiveSheetIndex(1);
        $this->setSheetChits($sheetChit, $data);

        return $spreadsheet;
    }

    /**
     * @param ArrayHash[] $data
     */
    private function setSheetEvents(Worksheet $sheet, array $data, bool $allowPragueColumns) : void
    {
        $firstElement = $data[array_key_first($data)];

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
            $functions = $row->func;

            assert($functions instanceof Functions);

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
                ->setCellValue('T' . $rowCnt, $row->prefix);
            if (isset($row->pragueParticipants)) {
                $pp = $row->pragueParticipants;

                assert($pp instanceof PragueParticipants);

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
        foreach (Range::letters('A', $lastColumn) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:' . $lastColumn . ($rowCnt - 1));
        $sheet->setTitle('Přehled akcí');
    }

    /**
     * @param ArrayHash[] $data
     */
    private function setSheetChits(Worksheet $sheet, array $data) : void
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
}
