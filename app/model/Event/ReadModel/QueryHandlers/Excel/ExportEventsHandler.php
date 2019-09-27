<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers\Excel;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\PragueParticipantsQuery;
use Model\Cashbook\ReadModel\QueryHandlers\Pdf\SheetChitsGenerator;
use Model\Cashbook\ReadModel\SpreadsheetFactory;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Event\ExportedCashbook;
use Model\DTO\Event\StatisticsItem;
use Model\Event\Event;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\ReadModel\Queries\EventQuery;
use Model\Event\ReadModel\Queries\EventScopes;
use Model\Event\ReadModel\Queries\EventTypes;
use Model\Event\ReadModel\Queries\Excel\ExportEvents;
use Model\Event\SkautisEventId;
use Model\Excel\Range;
use Model\Participant\PragueParticipants;
use Model\Skautis\ReadModel\Queries\EventStatisticsQuery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use function array_filter;
use function array_key_exists;
use function array_map;
use function assert;
use function count;

final class ExportEventsHandler
{
    /** @var QueryBus */
    private $queryBus;

    /** @var SpreadsheetFactory */
    private $spreadsheetFactory;

    /** @var SheetChitsGenerator */
    private $sheetChitsGenerator;

    public function __construct(
        QueryBus $queryBus,
        SpreadsheetFactory $spreadsheetFactory,
        SheetChitsGenerator $sheetChitsGenerator
    ) {
        $this->queryBus            = $queryBus;
        $this->spreadsheetFactory  = $spreadsheetFactory;
        $this->sheetChitsGenerator = $sheetChitsGenerator;
    }

    public function __invoke(ExportEvents $query) : Spreadsheet
    {
        $spreadsheet = $this->spreadsheetFactory->create();

        $events = array_map(
            function (int $eventId) : Event {
                return $this->queryBus->handle(new EventQuery(new SkautisEventId($eventId)));
            },
            $query->getEventIds()
        );

        $this->setSheetEvents($spreadsheet->setActiveSheetIndex(0), $events);
        ($this->sheetChitsGenerator)(
            $spreadsheet->createSheet(1),
            array_map(
                function (Event $event) : ExportedCashbook {
                    return new ExportedCashbook($this->getCashbookId($event), $event->getDisplayName());
                },
                $events
            )
        );

        return $spreadsheet;
    }

    /**
     * @param array<int, Event> $events
     */
    private function setSheetEvents(Worksheet $sheet, array $events) : void
    {
        $scopes                     = $this->queryBus->handle(new EventScopes());
        $types                      = $this->queryBus->handle(new EventTypes());
        $pragueParticipantsPerEvent = $this->getPragueParticipantsForEvents($events);

        foreach ($events as $index => $event) {
            assert($event instanceof Event);

            /** @var StatisticsItem[] $statistics */
            $statistics = $this->queryBus->handle(new EventStatisticsQuery($event->getId()));

            if ($index === 0) {
                $this->addHeader($sheet, $statistics, $pragueParticipantsPerEvent !== []);
            }

            $row = $index + 2;

            $functions = $this->queryBus->handle(new EventFunctions($event->getId()));
            assert($functions instanceof Functions);

            $leader     = $functions->getLeader() !== null ? $functions->getLeader()->getName() : null;
            $accountant = $functions->getAccountant() !== null ? $functions->getAccountant()->getName() : null;

            $sheet
                ->setCellValue('A' . $row, $event->getUnitName())
                ->setCellValue('B' . $row, $event->getDisplayName())
                ->setCellValue('C' . $row, $event->getUnitEducativeName() ?? '')
                ->setCellValue('D' . $row, $types[$event->getTypeId()])
                ->setCellValue('E' . $row, $scopes[$event->getScopeId()])
                ->setCellValue('F' . $row, $event->getLocation())
                ->setCellValue('G' . $row, $leader)
                ->setCellValue('H' . $row, $accountant)
                ->setCellValue('I' . $row, $event->getStartDate()->format('d.m.Y'))
                ->setCellValue('J' . $row, $event->getEndDate()->format('d.m.Y'))
                ->setCellValue('K' . $row, $event->getTotalDays())
                ->setCellValue('L' . $row, $event->getRealCount())
                ->setCellValue('M' . $row, $event->getRealPersonDays())
                ->setCellValue('N' . $row, $event->getRealChildDays())
                ->setCellValue('O' . $row, $statistics[1]->getCount())
                ->setCellValue('P' . $row, $statistics[2]->getCount())
                ->setCellValue('Q' . $row, $statistics[3]->getCount())
                ->setCellValue('R' . $row, $statistics[4]->getCount())
                ->setCellValue('S' . $row, $statistics[5]->getCount())
                ->setCellValue('T' . $row, $this->getCashbookPrefix($event));

            if (! array_key_exists($event->getId()->toInt(), $pragueParticipantsPerEvent)) {
                continue;
            }

            $pragueParticipants = $pragueParticipantsPerEvent[$event->getId()->toInt()];

            $sheet->setCellValue('U' . $row, $pragueParticipants->isSupportable($event->getTotalDays()) ? 'Ano' : 'Ne')
                ->setCellValue('V' . $row, $pragueParticipants->getPersonDaysUnder26())
                ->setCellValue('W' . $row, $pragueParticipants->getUnder18())
                ->setCellValue('X' . $row, $pragueParticipants->getBetween18and26())
                ->setCellValue('Y' . $row, $pragueParticipants->getCitizensCount());
        }

        $lastColumn = $pragueParticipantsPerEvent !== [] ? 'W' : 'S';

        //format
        foreach (Range::letters('A', $lastColumn) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:' . $lastColumn . (count($events) + 2));
        $sheet->setTitle('Přehled akcí');
    }

    /**
     * @param StatisticsItem[] $statistics
     */
    private function addHeader(Worksheet $sheet, array $statistics, bool $allowPragueColumns) : void
    {
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
            ->setCellValue('O1', $statistics[1]->getLabel())
            ->setCellValue('P1', $statistics[2]->getLabel())
            ->setCellValue('Q1', $statistics[3]->getLabel())
            ->setCellValue('R1', $statistics[4]->getLabel())
            ->setCellValue('S1', $statistics[5]->getLabel())
            ->setCellValue('T1', 'Prefix');

        if (! $allowPragueColumns) {
            return;
        }

        $sheet->setCellValue('U1', 'Dotovatelná MHMP?')
            ->setCellValue('V1', 'Praž. osobodny pod 26')
            ->setCellValue('W1', 'Praž. uč. pod 18')
            ->setCellValue('X1', 'Praž. uč. mezi 18 a 26')
            ->setCellValue('Y1', 'Praž. uč. celkem');
        $sheet->getComment('U1')
            ->setWidth('200pt')->setHeight('50pt')->getText()
            ->createTextRun(
                'Ověřte, zda jsou splněny další podmínky - např. akce konaná v době mimo školní '
                . 'vyučování (u táborů prázdnin), cílovou skupinou je studující mládež do 26 let.'
            );
    }

    private function getCashbookId(Event $event) : CashbookId
    {
        return $this->queryBus->handle(new EventCashbookIdQuery($event->getId()));
    }

    /**
     * @param Event[] $events
     *
     * @return array<int, PragueParticipants> Prague participants for events that has some, indexed by event ID
     */
    private function getPragueParticipantsForEvents(array $events) : array
    {
        return array_filter(
            array_map(
                function (Event $event) : ?PragueParticipants {
                    return $this->queryBus->handle(new PragueParticipantsQuery(
                        $event->getId(),
                        $event->getRegistrationNumber(),
                        $event->getStartDate()
                    ));
                },
                $events
            )
        );
    }

    private function getCashbookPrefix(Event $event) : ?string
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->getCashbookId($event)));
        assert($cashbook instanceof Cashbook);

        return $cashbook->getChitNumberPrefix();
    }
}
