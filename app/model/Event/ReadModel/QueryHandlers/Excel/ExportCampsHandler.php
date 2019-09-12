<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers\Excel;

use Assert\Assertion;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\QueryHandlers\Pdf\SheetChitsGenerator;
use Model\Cashbook\ReadModel\SpreadsheetFactory;
use Model\DTO\Event\ExportedCashbook;
use Model\Event\Camp;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\ReadModel\Queries\CampQuery;
use Model\Event\ReadModel\Queries\Excel\ExportCamps;
use Model\Event\SkautisCampId;
use Model\Excel\Range;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\UnitNotFound;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use function array_map;
use function assert;
use function implode;

final class ExportCampsHandler
{
    /** @var QueryBus */
    private $queryBus;

    /** @var SpreadsheetFactory */
    private $spreadsheetFactory;

    /** @var IUnitRepository */
    private $unitRepository;

    /** @var SheetChitsGenerator */
    private $sheetChitsGenerator;

    public function __construct(
        QueryBus $queryBus,
        SpreadsheetFactory $spreadsheetFactory,
        IUnitRepository $units,
        SheetChitsGenerator $sheetChitsGenerator
    ) {
        $this->queryBus            = $queryBus;
        $this->spreadsheetFactory  = $spreadsheetFactory;
        $this->unitRepository      = $units;
        $this->sheetChitsGenerator = $sheetChitsGenerator;
    }

    public function __invoke(ExportCamps $query) : Spreadsheet
    {
        $spreadsheet = $this->spreadsheetFactory->create();

        $camps = array_map(
            function (int $campId) : Camp {
                return $this->queryBus->handle(new CampQuery(new SkautisCampId($campId)));
            },
            $query->getCampIds()
        );

        $sheetCamps = $spreadsheet->setActiveSheetIndex(0);
        $this->setSheetCamps($sheetCamps, $camps);
        $spreadsheet->createSheet(1);

        ($this->sheetChitsGenerator)(
            $spreadsheet->createSheet(1),
            array_map(
                function (Camp $camp) : ExportedCashbook {
                    return new ExportedCashbook(
                        $this->queryBus->handle(new CampCashbookIdQuery($camp->getId())),
                        $camp->getDisplayName()
                    );
                },
                $camps
            )
        );

        return $spreadsheet;
    }

    /**
     * @param Camp[] $camps
     */
    protected function setSheetCamps(Worksheet $sheet, array $camps) : void
    {
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
        foreach ($camps as $camp) {
            $functions = $this->queryBus->handle(new CampFunctions($camp->getId()));
            assert($functions instanceof Functions);

            $leader     = $functions->getLeader() !== null ? $functions->getLeader()->getName() : null;
            $accountant = $functions->getAccountant() !== null ? $functions->getAccountant()->getName() : null;

            $statistics = $camp->getParticipantStatistics();

            Assertion::notNull($statistics);

            $sheet->setCellValue('A' . $rowCnt, $camp->getUnitName())
                ->setCellValue('B' . $rowCnt, $camp->getDisplayName())
                ->setCellValue('C' . $rowCnt, implode(', ', $this->getCampTroopNames($camp)))
                ->setCellValue('D' . $rowCnt, $camp->getLocation())
                ->setCellValue('E' . $rowCnt, $leader)
                ->setCellValue('F' . $rowCnt, $accountant)
                ->setCellValue('G' . $rowCnt, $camp->getStartDate()->format('d.m.Y'))
                ->setCellValue('H' . $rowCnt, $camp->getEndDate()->format('d.m.Y'))
                ->setCellValue('I' . $rowCnt, $camp->getTotalDays())
                ->setCellValue('J' . $rowCnt, $statistics->getRealCount())
                ->setCellValue('K' . $rowCnt, $statistics->getRealAdult())
                ->setCellValue('L' . $rowCnt, $statistics->getRealChild())
                ->setCellValue('M' . $rowCnt, $statistics->getRealPersonDays())
                ->setCellValue('N' . $rowCnt, $statistics->getRealChildDays());
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
