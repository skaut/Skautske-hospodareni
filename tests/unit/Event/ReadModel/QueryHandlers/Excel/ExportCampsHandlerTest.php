<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\QueryHandlers\Excel;

use App\Model\Cashbook\Cashbook\Amount;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Cashbook\ChitBody;
use App\Model\Cashbook\Cashbook\ChitNumber;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\Cashbook\Recipient;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\QueryHandlers\Pdf\SheetChitsGenerator;
use App\Model\Cashbook\ReadModel\SpreadsheetFactory;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\DTO\Cashbook\Category;
use App\Model\DTO\Cashbook\Chit;
use App\Model\DTO\Cashbook\ChitItem;
use App\Model\Event\Camp;
use App\Model\Event\Functions;
use App\Model\Event\ParticipantStatistics;
use App\Model\Event\Person;
use App\Model\Event\ReadModel\Queries\CampFunctions;
use App\Model\Event\ReadModel\Queries\CampQuery;
use App\Model\Event\ReadModel\Queries\Excel\ExportCamps;
use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;
use App\Model\Unit\UnitNotFound;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit as UnitTest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Export táborů do XLSX. Kromě obsahu listu hlídá i chování při zrušeném podřízeném oddílu,
 * který Skautis pořád vrací (viz issue #483).
 */
final class ExportCampsHandlerTest extends UnitTest
{
    public function testCampSheetContainsCampDataWithParticipatingTroops(): void
    {
        $sheet = $this->export()->setActiveSheetIndex(0);

        self::assertSame('Přehled táborů', $sheet->getTitle());
        self::assertSame('Pořadatel', $sheet->getCell('A1')->getValue());
        self::assertSame('Dětodnů', $sheet->getCell('N1')->getValue());

        self::assertSame('Středisko Test', $sheet->getCell('A2')->getValue());
        self::assertSame('Tábor Lhota', $sheet->getCell('B2')->getValue());
        self::assertSame('1. oddíl, 2. oddíl', $sheet->getCell('C2')->getValue());
        self::assertSame('Lhota', $sheet->getCell('D2')->getValue());
        self::assertSame('Jan Vedoucí', $sheet->getCell('E2')->getValue());
        self::assertSame('Eva Hospodářka', $sheet->getCell('F2')->getValue());
        self::assertSame('01.07.2026', $sheet->getCell('G2')->getValue());
        self::assertSame('14.07.2026', $sheet->getCell('H2')->getValue());
        self::assertSame(14, $sheet->getCell('I2')->getValue());
        self::assertSame(30, $sheet->getCell('J2')->getValue());
        self::assertSame(6, $sheet->getCell('K2')->getValue());
        self::assertSame(24, $sheet->getCell('L2')->getValue());
        self::assertSame(420, $sheet->getCell('M2')->getValue());
        self::assertSame(336, $sheet->getCell('N2')->getValue());

        self::assertTrue($sheet->getStyle('A1')->getFont()->getBold());
        self::assertSame('A1:N2', $sheet->getAutoFilter()->getRange());
    }

    public function testRemovedTroopIsSkippedInsteadOfFailingTheExport(): void
    {
        $sheet = $this->export(missingTroopId: 2)->setActiveSheetIndex(0);

        self::assertSame('1. oddíl', $sheet->getCell('C2')->getValue());
    }

    public function testChitSheetIsGeneratedForCampCashbook(): void
    {
        $spreadsheet = $this->export();

        // Handler vytváří dva listy (createSheet(1) dvakrát), doklady jdou do toho druhého,
        // takže skončí na indexu 1 a prázdný list se odsune na 2.
        self::assertSame('Worksheet', $spreadsheet->setActiveSheetIndex(2)->getTitle());

        $sheet = $spreadsheet->setActiveSheetIndex(1);
        self::assertSame('Doklady', $sheet->getTitle());
        self::assertSame('Tábor Lhota', $sheet->getCell('A2')->getValue());
        self::assertSame('T1', $sheet->getCell('D2')->getValue());
        self::assertSame(500.0, $sheet->getCell('H2')->getValue());
    }

    private function export(?int $missingTroopId = null): Spreadsheet
    {
        $queryBus = new ExportCampsQueryBusStub();
        $handler = new ExportCampsHandler(
            $queryBus,
            new SpreadsheetFactory(),
            new ExportCampsUnitRepositoryStub($missingTroopId),
            new SheetChitsGenerator($queryBus),
        );

        return $handler(new ExportCamps([21]));
    }
}

final class ExportCampsQueryBusStub implements QueryBus
{
    private const CASHBOOK_ID = '22222222-2222-4222-8222-222222222222';

    public function handle(object $query): mixed
    {
        if ($query instanceof CampQuery) {
            return new Camp(
                $query->getCampId(),
                'Tábor Lhota',
                new UnitId(1),
                'Středisko Test',
                new ChronosDate('2026-07-01'),
                new ChronosDate('2026-07-14'),
                'Lhota',
                'draft',
                '123.45',
                [new UnitId(1), new UnitId(2)],
                false,
                14,
                new ParticipantStatistics(6, 24, 30, 336, 420),
            );
        }

        if ($query instanceof CampFunctions) {
            return new Functions(new Person(1, 'Jan Vedoucí'), null, new Person(2, 'Eva Hospodářka'));
        }

        if ($query instanceof CampCashbookIdQuery) {
            return CashbookId::fromString(self::CASHBOOK_ID);
        }

        if ($query instanceof CashbookQuery) {
            return new Cashbook(
                CashbookId::fromString(self::CASHBOOK_ID),
                CashbookType::get(CashbookType::CAMP),
                'T',
                'B',
                '',
                true,
                true,
            );
        }

        return [
            new Chit(
                1,
                new ChitBody(new ChitNumber('1'), new ChronosDate('2026-07-10'), new Recipient('Jan Novák')),
                false,
                [],
                PaymentMethod::CASH(),
                [new ChitItem(new Amount('500'), new Category(1, 'Přijmy od účastníků', 'up', Operation::INCOME(), false), 'účastnické poplatky')],
                Operation::INCOME(),
                new Amount('500'),
                [],
            ),
        ];
    }
}

final class ExportCampsUnitRepositoryStub implements IUnitRepository
{
    public function __construct(private ?int $missingUnitId = null)
    {
    }

    /** @return Unit[] */
    public function findByParent(int $parentId): array
    {
        return [];
    }

    public function find(int $id): Unit
    {
        if ($id === $this->missingUnitId) {
            throw new UnitNotFound();
        }

        return new Unit($id, $id.'. oddil', $id.'. oddíl', null, 'Ulice', 'Praha', '11000', '123.4'.$id, 'oddil', 1);
    }
}
