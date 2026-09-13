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
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EventPragueParticipantsQuery;
use App\Model\Cashbook\ReadModel\QueryHandlers\Pdf\SheetChitsGenerator;
use App\Model\Cashbook\ReadModel\SpreadsheetFactory;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\DTO\Cashbook\Category;
use App\Model\DTO\Cashbook\Chit;
use App\Model\DTO\Cashbook\ChitItem;
use App\Model\DTO\Event\StatisticsItem;
use App\Model\DTO\Participant\Participant;
use App\Model\Event\Event;
use App\Model\Event\Functions;
use App\Model\Event\Person;
use App\Model\Event\ReadModel\Queries\EventFunctions;
use App\Model\Event\ReadModel\Queries\EventQuery;
use App\Model\Event\ReadModel\Queries\EventScopes;
use App\Model\Event\ReadModel\Queries\EventTypes;
use App\Model\Event\ReadModel\Queries\Excel\ExportEvents;
use App\Model\Participant\Payment;
use App\Model\Participant\Payment\Event as PaymentEvent;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\PaymentId;
use App\Model\Participant\PragueParticipants;
use App\Model\Skautis\ReadModel\Queries\EventStatisticsQuery;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;

/**
 * Exportuje reálný XLSX (dva listy) — ověřuje obsah buněk včetně listu s doklady,
 * který generuje {@see SheetChitsGenerator}.
 */
final class ExportEventsHandlerTest extends Unit
{
    public function testEventSheetContainsEventDataStatisticsAndCashbookPrefix(): void
    {
        $queryBus = new ExportEventsQueryBusStub();
        $handler = new ExportEventsHandler($queryBus, new SpreadsheetFactory(), new SheetChitsGenerator($queryBus));

        $spreadsheet = $handler(new ExportEvents([11]));
        $sheet = $spreadsheet->setActiveSheetIndex(0);

        self::assertSame('Přehled akcí', $sheet->getTitle());

        // hlavička se skládá až z prvního záznamu (labely statistik chodí ze Skautisu)
        self::assertSame('Pořadatel', $sheet->getCell('A1')->getValue());
        self::assertSame('Prefix', $sheet->getCell('T1')->getValue());
        self::assertSame('Dětí', $sheet->getCell('O1')->getValue());

        self::assertSame('Středisko Test', $sheet->getCell('A2')->getValue());
        self::assertSame('Letní výprava', $sheet->getCell('B2')->getValue());
        self::assertSame('1. oddíl', $sheet->getCell('C2')->getValue());
        self::assertSame('Výprava', $sheet->getCell('D2')->getValue());
        self::assertSame('oddílová', $sheet->getCell('E2')->getValue());
        self::assertSame('Beskydy', $sheet->getCell('F2')->getValue());
        self::assertSame('Jan Vedoucí', $sheet->getCell('G2')->getValue());
        self::assertSame('Eva Hospodářka', $sheet->getCell('H2')->getValue());
        self::assertSame('01.07.2026', $sheet->getCell('I2')->getValue());
        self::assertSame('05.07.2026', $sheet->getCell('J2')->getValue());
        self::assertSame(5, $sheet->getCell('K2')->getValue());
        self::assertSame(12, $sheet->getCell('L2')->getValue());
        self::assertSame(60, $sheet->getCell('M2')->getValue());
        self::assertSame(40, $sheet->getCell('N2')->getValue());
        self::assertSame(3, $sheet->getCell('O2')->getValue());
        self::assertSame('H', $sheet->getCell('T2')->getValue());

        self::assertTrue($sheet->getStyle('A1')->getFont()->getBold());
        self::assertTrue($sheet->getColumnDimension('S')->getAutoSize());
    }

    public function testPragueColumnsAreAddedOnlyWhenEventHasPragueParticipants(): void
    {
        $withPrague = new ExportEventsQueryBusStub(pragueParticipants: true);
        $handler = new ExportEventsHandler($withPrague, new SpreadsheetFactory(), new SheetChitsGenerator($withPrague));

        $sheet = $handler(new ExportEvents([11]))->setActiveSheetIndex(0);

        self::assertSame('Dotovatelná MHMP?', $sheet->getCell('U1')->getValue());
        self::assertSame('Praž. uč. celkem', $sheet->getCell('Y1')->getValue());
        self::assertSame('Ne', $sheet->getCell('U2')->getValue(), 'jeden pražský účastník na dotaci nestačí');
        self::assertSame(1, $sheet->getCell('Y2')->getValue());
    }

    public function testEventWithoutFunctionsLeavesLeaderAndAccountantEmpty(): void
    {
        $queryBus = new ExportEventsQueryBusStub(functions: false);
        $handler = new ExportEventsHandler($queryBus, new SpreadsheetFactory(), new SheetChitsGenerator($queryBus));

        $sheet = $handler(new ExportEvents([11]))->setActiveSheetIndex(0);

        self::assertNull($sheet->getCell('G2')->getValue());
        self::assertNull($sheet->getCell('H2')->getValue());
    }

    public function testChitSheetListsChitsOfEveryExportedCashbook(): void
    {
        $queryBus = new ExportEventsQueryBusStub();
        $handler = new ExportEventsHandler($queryBus, new SpreadsheetFactory(), new SheetChitsGenerator($queryBus));

        $sheet = $handler(new ExportEvents([11, 12]))->setActiveSheetIndex(1);

        self::assertSame('Doklady', $sheet->getTitle());
        self::assertSame('Název akce', $sheet->getCell('A1')->getValue());

        self::assertSame('Letní výprava', $sheet->getCell('A2')->getValue());
        self::assertSame('10.07.2026', $sheet->getCell('B2')->getValue());
        self::assertSame('Pokladna', $sheet->getCell('C2')->getValue());
        self::assertSame('H1', $sheet->getCell('D2')->getValue());
        self::assertSame('účastnické poplatky', $sheet->getCell('E2')->getValue());
        self::assertSame('Přijmy od účastníků', $sheet->getCell('F2')->getValue());
        self::assertSame('Jan Novák', $sheet->getCell('G2')->getValue());
        self::assertSame(500.0, $sheet->getCell('H2')->getValue());
        self::assertSame('', $sheet->getCell('I2')->getValue());

        self::assertSame('', $sheet->getCell('H3')->getValue());
        self::assertSame(120.0, $sheet->getCell('I3')->getValue(), 'výdaj patří do sloupce Výdej');

        // druhá akce navazuje na stejném listu
        self::assertSame('Letní výprava', $sheet->getCell('A4')->getValue());
        self::assertSame('A1:H5', $sheet->getAutoFilter()->getRange());
    }
}

final class ExportEventsQueryBusStub implements QueryBus
{
    public function __construct(private bool $pragueParticipants = false, private bool $functions = true)
    {
    }

    public function handle(object $query): mixed
    {
        if ($query instanceof EventQuery) {
            return new Event(
                $query->getEventId(),
                'Letní výprava',
                new UnitId(1),
                'Středisko Test',
                'draft',
                new ChronosDate('2026-07-01'),
                new ChronosDate('2026-07-05'),
                5,
                'Beskydy',
                '123.45',
                null,
                7,
                3,
                false,
                12,
                40,
                60,
                null,
                null,
                '1. oddíl',
            );
        }

        if ($query instanceof EventScopes) {
            return [7 => 'oddílová'];
        }

        if ($query instanceof EventTypes) {
            return [3 => 'Výprava'];
        }

        if ($query instanceof EventStatisticsQuery) {
            return [
                1 => new StatisticsItem('Dětí', 3),
                2 => new StatisticsItem('Vedoucích', 2),
                3 => new StatisticsItem('Zahraničních', 0),
                4 => new StatisticsItem('Hostů', 1),
                5 => new StatisticsItem('Celkem', 6),
            ];
        }

        if ($query instanceof EventFunctions) {
            return $this->functions
                ? new Functions(new Person(1, 'Jan Vedoucí'), null, new Person(2, 'Eva Hospodářka'))
                : new Functions();
        }

        if ($query instanceof EventCashbookIdQuery) {
            return CashbookId::fromString('11111111-1111-4111-8111-111111111111');
        }

        if ($query instanceof EventPragueParticipantsQuery) {
            return $this->pragueParticipants
                ? PragueParticipants::fromParticipantList(new ChronosDate('2026-07-01'), [$this->praguePartipant()])
                : null;
        }

        if ($query instanceof CashbookQuery) {
            return new Cashbook(
                CashbookId::fromString('11111111-1111-4111-8111-111111111111'),
                CashbookType::get(CashbookType::EVENT),
                'H',
                'B',
                '',
                true,
                true,
            );
        }

        return $this->chits();
    }

    private function praguePartipant(): Participant
    {
        return new Participant(
            1,
            101,
            'Jan',
            'Pražák',
            null,
            10,
            new ChronosDate('2016-01-01'),
            'Ulice',
            'Praha 4',
            14000,
            'accepted',
            'Oddíl',
            '11123.45',
            5,
            true,
            new Payment(
                PaymentId::generate(),
                1,
                new PaymentEvent(11, EventType::GENERAL()),
                MoneyFactory::zero(),
                MoneyFactory::zero(),
                'N',
            ),
            null,
        );
    }

    /** @return Chit[] */
    private function chits(): array
    {
        $income = new Category(1, 'Přijmy od účastníků', 'up', Operation::INCOME(), false);
        $expense = new Category(2, 'Potraviny', 'pot', Operation::EXPENSE(), false);

        return [
            new Chit(
                1,
                new ChitBody(new ChitNumber('1'), new ChronosDate('2026-07-10'), new Recipient('Jan Novák')),
                false,
                [],
                PaymentMethod::CASH(),
                [new ChitItem(new Amount('500'), $income, 'účastnické poplatky')],
                Operation::INCOME(),
                new Amount('500'),
                [],
            ),
            new Chit(
                2,
                new ChitBody(new ChitNumber('2'), new ChronosDate('2026-07-11'), new Recipient('Eva Malá')),
                false,
                [],
                PaymentMethod::BANK(),
                [new ChitItem(new Amount('120'), $expense, 'nákup potravin')],
                Operation::EXPENSE(),
                new Amount('120'),
                [],
            ),
        ];
    }
}
