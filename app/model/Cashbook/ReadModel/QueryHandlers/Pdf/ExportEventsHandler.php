<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers\Pdf;

use Cake\Chronos\Date;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportEvents;
use Model\Cashbook\ReadModel\SpreadsheetFactory;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\Event;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\ReadModel\Queries\EventScopes;
use Model\Event\ReadModel\Queries\EventTypes;
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

    /** @var SheetChitsGenerator */
    private $sheetChitsGenerator;

    public function __construct(
        IParticipantServiceFactory $participantServiceFactory,
        IEventServiceFactory $serviceFactory,
        QueryBus $queryBus,
        SpreadsheetFactory $spreadsheetFactory,
        SheetChitsGenerator $sheetChitsGenerator
    ) {
        $this->participantServiceFactory = $participantServiceFactory;
        $this->serviceFactory            = $serviceFactory;
        $this->queryBus                  = $queryBus;
        $this->spreadsheetFactory        = $spreadsheetFactory;
        $this->sheetChitsGenerator       = $sheetChitsGenerator;
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

            $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));
            assert($cashbook instanceof Cashbook);

            $event = $this->queryBus->handle(new EventQuery(new SkautisEventId($aid)));
            assert($event instanceof Event);

            $data[$aid]                    = new ArrayHash();
            $data[$aid]['event']           = $event;
            $data[$aid]['cashbookId']      = $cashbookId;
            $data[$aid]['parStatistic']    = $participantService->getEventStatistic($aid);
            $data[$aid]['chits']           = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId));
            $data[$aid]['func']            = $this->queryBus->handle(new EventFunctions($eventId));
            $participants                  = $participantService->getAll($aid);
            $data[$aid]['participantsCnt'] = count($participants);
            $data[$aid]['personDays']      = $participantService->getPersonsDays($participants);
            $data[$aid]['prefix']          = $cashbook->getChitNumberPrefix();

            $pp = $participantService->countPragueParticipants(
                $data[$aid]['event']->getRegistrationNumber(),
                new Date($data[$aid]['event']->getStartDate()),
                $data[$aid]['event']->getID()->toInt()
            );
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
        ($this->sheetChitsGenerator)($sheetChit, $data);

        return $spreadsheet;
    }

    /**
     * @param ArrayHash[] $data
     */
    private function setSheetEvents(Worksheet $sheet, array $data, bool $allowPragueColumns) : void
    {
        $scopes = $this->queryBus->handle(new EventScopes());
        $types  = $this->queryBus->handle(new EventTypes());

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
            $event     = $row['event'];

            assert($functions instanceof Functions);
            assert($event instanceof Event);

            $leader     = $functions->getLeader() !== null ? $functions->getLeader()->getName() : null;
            $accountant = $functions->getAccountant() !== null ? $functions->getAccountant()->getName() : null;
            $sheet->setCellValue('A' . $rowCnt, $event->getUnitName())
                ->setCellValue('B' . $rowCnt, $event->getDisplayName())
                ->setCellValue('C' . $rowCnt, $event->getUnitEducativeName() ?? '')
                ->setCellValue('D' . $rowCnt, $types[$event->getTypeId()])
                ->setCellValue('E' . $rowCnt, $scopes[$event->getScopeId()])
                ->setCellValue('F' . $rowCnt, $event->getLocation())
                ->setCellValue('G' . $rowCnt, $leader)
                ->setCellValue('H' . $rowCnt, $accountant)
                ->setCellValue('I' . $rowCnt, $event->getStartDate()->format('d.m.Y'))
                ->setCellValue('J' . $rowCnt, $event->getEndDate()->format('d.m.Y'))
                ->setCellValue('K' . $rowCnt, $event->getTotalDays())
                ->setCellValue('L' . $rowCnt, $event->getRealCount())
                ->setCellValue('M' . $rowCnt, $event->getRealPersonDays())
                ->setCellValue('N' . $rowCnt, $event->getRealChildDays())
                ->setCellValue('O' . $rowCnt, $row->parStatistic[1]->Count)
                ->setCellValue('P' . $rowCnt, $row->parStatistic[2]->Count)
                ->setCellValue('Q' . $rowCnt, $row->parStatistic[3]->Count)
                ->setCellValue('R' . $rowCnt, $row->parStatistic[4]->Count)
                ->setCellValue('S' . $rowCnt, $row->parStatistic[5]->Count)
                ->setCellValue('T' . $rowCnt, $row['prefix']);
            if (isset($row->pragueParticipants)) {
                $pp = $row->pragueParticipants;

                assert($pp instanceof PragueParticipants);

                $sheet->setCellValue('U' . $rowCnt, $pp->isSupportable($event->getTotalDays()) ? 'Ano' : 'Ne')
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
}
