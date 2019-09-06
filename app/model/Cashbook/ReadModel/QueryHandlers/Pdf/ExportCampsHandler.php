<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers\Pdf;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportCamps;
use Model\Cashbook\ReadModel\SpreadsheetFactory;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\Event\Camp;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\SkautisCampId;
use Model\Excel\Range;
use Model\IEventServiceFactory;
use Model\IParticipantServiceFactory;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\UnitNotFound;
use Nette\Utils\ArrayHash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use function assert;
use function count;
use function date;
use function implode;
use function reset;
use function strtotime;
use function ucfirst;

class ExportCampsHandler
{
    /** @var IParticipantServiceFactory */
    private $participantServiceFactory;

    /** @var IEventServiceFactory */
    private $serviceFactory;

    /** @var QueryBus */
    private $queryBus;

    /** @var SpreadsheetFactory */
    private $spreadsheetFactory;

    /** @var IUnitRepository */
    private $unitRepository;

    /** @var SheetChitsGenerator */
    private $sheetChitsGenerator;

    public function __construct(
        IParticipantServiceFactory $participantServiceFactory,
        IEventServiceFactory $serviceFactory,
        QueryBus $queryBus,
        SpreadsheetFactory $spreadsheetFactory,
        IUnitRepository $units,
        SheetChitsGenerator $sheetChitsGenerator
    ) {
        $this->participantServiceFactory = $participantServiceFactory;
        $this->serviceFactory            = $serviceFactory;
        $this->queryBus                  = $queryBus;
        $this->spreadsheetFactory        = $spreadsheetFactory;
        $this->unitRepository            = $units;
        $this->sheetChitsGenerator       = $sheetChitsGenerator;
    }

    public function __invoke(ExportCamps $query) : Spreadsheet
    {
        $eventService       = $this->serviceFactory->create(ucfirst(ObjectType::CAMP));
        $participantService = $this->participantServiceFactory->create(ucfirst(ObjectType::CAMP));
        $spreadsheet        = $this->spreadsheetFactory->create();

        $data = [];
        foreach ($query->getCampIds() as $aid) {
            $campId     = new SkautisCampId($aid);
            $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($campId));
            assert($cashbookId instanceof CashbookId);
            $camp = $this->queryBus->handle(new CampQuery($campId));
            assert($camp instanceof Camp);
            $data[$aid]                    = ArrayHash::from($camp);
            $data[$aid]['cashbookId']      = $cashbookId;
            $data[$aid]['troops']          = implode(', ', $this->getCampTroopNames($camp));
            $data[$aid]['chits']           = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId));
            $data[$aid]['func']            = $this->queryBus->handle(new CampFunctions(new SkautisCampId($aid)));
            $participants                  = $participantService->getAll($aid);
            $data[$aid]['participantsCnt'] = count($participants);
            $data[$aid]['personDays']      = $participantService->getPersonsDays($participants);
        }
        $sheetCamps = $spreadsheet->setActiveSheetIndex(0);
        $this->setSheetCamps($sheetCamps, $data);
        $spreadsheet->createSheet(1);
        $sheetChit = $spreadsheet->setActiveSheetIndex(1);
        ($this->sheetChitsGenerator)($sheetChit, $data);

        return $spreadsheet;
    }

    /**
     * @param ArrayHash[] $data
     */
    protected function setSheetCamps(Worksheet $sheet, array $data) : void
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
            $functions = $row->func;

            assert($functions instanceof Functions);

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
        foreach (Range::letters('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:N' . ($rowCnt - 1));
        $sheet->setTitle('Přehled táborů');
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

    /**
     * @return string[]
     */
    private function getCampTroopNames(Camp $camp) : array
    {
        $troopNames = [];
        foreach ($camp->getParticipatingUnits() as $troopId) {
            try {
                $unit = $this->unitRepository->find($troopId->toInt());
            } catch (UnitNotFound $e) {
                // Removed troops are returned as well https://github.com/skaut/Skautske-hospodareni/issues/483
                continue;
            }
            $troopNames[] = $unit->getDisplayName();
        }

        return $troopNames;
    }
}
