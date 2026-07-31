<?php

declare(strict_types=1);

namespace App\Model\Excel;

use App\Model\Cashbook\Cashbook\Amount;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Cashbook\ChitBody;
use App\Model\Cashbook\Cashbook\ChitNumber;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\Cashbook\Recipient;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitListQuery;
use App\Model\Common\EmailAddress;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\DTO\Cashbook\Category;
use App\Model\DTO\Cashbook\Chit;
use App\Model\DTO\Cashbook\ChitItem;
use App\Model\DTO\Participant\Participant;
use App\Model\DTO\Payment\Payment as PaymentDto;
use App\Model\Participant\Payment;
use App\Model\Participant\Payment\Event;
use App\Model\Participant\Payment\EventType;
use App\Model\Participant\PaymentId;
use App\Model\Payment\Payment\State;
use App\Model\Payment\VariableSymbol;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Testuje reálně vygenerovaný Spreadsheet — ověřuje obsah buněk, ne to, že se metoda zavolala.
 */
final class ExcelServiceTest extends Unit
{
    private ExcelService $service;

    protected function _before(): void
    {
        $this->service = new ExcelService(new ExcelServiceQueryBusStub());
    }

    public function testGeneralParticipantsSheetCountsChildDaysByStartDate(): void
    {
        $sheet = $this->service->getGeneralParticipants(
            [
                ExcelServiceFixtures::participant(1, 'Jan', 'Novák', new ChronosDate('2015-05-01'), 300.0, 0.0, 5),
                ExcelServiceFixtures::participant(2, 'Petr', 'Starý', new ChronosDate('1980-01-01'), 500.0, 0.0, 7),
                ExcelServiceFixtures::participant(3, 'Bez', 'Data', null, 100.0, 0.0, 3),
            ],
            new ChronosDate('2026-07-01'),
        )->getActiveSheet();

        self::assertSame('Seznam účastníků', $sheet->getTitle());
        self::assertSame('P.č.', $sheet->getCell('A1')->getValue());
        self::assertSame('Zaplaceno', $sheet->getCell('L1')->getValue());

        self::assertSame('Jan', $sheet->getCell('B2')->getValue());
        self::assertSame('Novák', $sheet->getCell('C2')->getValue());
        self::assertSame('01.05.2015', $sheet->getCell('H2')->getValue());
        self::assertSame(123.45, $sheet->getCell('I2')->getValue(), 'PhpSpreadsheet numerický string převede na číslo');
        self::assertSame(5, $sheet->getCell('K2')->getValue(), 'dítě: dětodny = osobodny');
        self::assertSame(300.0, $sheet->getCell('L2')->getValue());

        self::assertSame(0, $sheet->getCell('K3')->getValue(), 'dospělý: nula dětodnů');
        self::assertSame('', $sheet->getCell('H4')->getValue(), 'bez data narození: prázdná buňka');
        self::assertSame(0, $sheet->getCell('K4')->getValue());

        self::assertTrue($sheet->getStyle('A1')->getFont()->getBold());
        self::assertTrue($sheet->getColumnDimension('L')->getAutoSize());
    }

    public function testCampParticipantsSheetUsesAgeForChildDaysAndAccountFlag(): void
    {
        $sheet = $this->service->getCampParticipants([
            ExcelServiceFixtures::participant(1, 'Jan', 'Novák', new ChronosDate('2015-05-01'), 300.0, 50.0, 5, 11, 'Y'),
            ExcelServiceFixtures::participant(2, 'Petr', 'Starý', new ChronosDate('1980-01-01'), 500.0, 0.0, 7, 46),
        ])->getActiveSheet();

        self::assertSame('Dětodny', $sheet->getCell('J1')->getValue());
        self::assertSame(5, $sheet->getCell('J2')->getValue());
        self::assertSame(0, $sheet->getCell('J3')->getValue());

        self::assertSame(300.0, $sheet->getCell('K2')->getValue());
        self::assertSame(50.0, $sheet->getCell('L2')->getValue());
        self::assertSame(250.0, $sheet->getCell('M2')->getValue(), 'celkem = zaplaceno - vratka');
        self::assertSame('Ano', $sheet->getCell('N2')->getValue());
        self::assertSame('Ne', $sheet->getCell('N3')->getValue());
    }

    public function testEducationParticipantsSheetHasAutoFilterOverAllRows(): void
    {
        $sheet = $this->service->getEducationParticipants([
            ExcelServiceFixtures::participant(1, 'Jan', 'Novák', new ChronosDate('2000-05-01'), 300.0, 20.0, 2),
            ExcelServiceFixtures::participant(2, 'Eva', 'Malá', null, 100.0, 0.0, 2),
        ])->getActiveSheet();

        self::assertSame('Na účet', $sheet->getCell('L1')->getValue());
        self::assertSame(280.0, $sheet->getCell('K2')->getValue());
        self::assertSame('A1:L3', $sheet->getAutoFilter()->getRange());
    }

    public function testCashbookSheetComputesRunningBalanceAndPrefixedChitNumbers(): void
    {
        $sheet = $this->service->getCashbook(CashbookId::generate(), PaymentMethod::CASH())->getActiveSheet();

        self::assertSame('Evidence plateb', $sheet->getTitle());
        self::assertSame('Zůstatek', $sheet->getCell('H1')->getValue());

        // příjem 500 Kč
        self::assertSame('10.07.2026', $sheet->getCell('A2')->getValue());
        self::assertSame('H1', $sheet->getCell('B2')->getValue(), 'číslo dokladu s prefixem pokladny');
        self::assertSame('účastnické poplatky', $sheet->getCell('C2')->getValue());
        self::assertSame('Přijmy od účastníků', $sheet->getCell('D2')->getValue());
        self::assertSame('Jan Novák', $sheet->getCell('E2')->getValue());
        self::assertSame(500.0, $sheet->getCell('F2')->getValue());
        self::assertSame('', $sheet->getCell('G2')->getValue());
        self::assertSame(500.0, $sheet->getCell('H2')->getValue());

        // výdaj 120 Kč → zůstatek 380 Kč
        self::assertSame('', $sheet->getCell('F3')->getValue());
        self::assertSame(120.0, $sheet->getCell('G3')->getValue());
        self::assertSame(380.0, $sheet->getCell('H3')->getValue());
        self::assertSame('nákup potravin, kancelář', $sheet->getCell('C3')->getValue());

        self::assertTrue($sheet->getStyle('H2')->getFont()->getBold(), 'zůstatek je zvýrazněný');
    }

    public function testCashbookWithCategoriesAndItemsUseCategoryBuilder(): void
    {
        $cashbookId = CashbookId::generate();

        $spreadsheets = [
            $this->service->getCashbookWithCategories($cashbookId, PaymentMethod::CASH()),
            $this->service->getCashbookItems($cashbookId, PaymentMethod::CASH()),
        ];

        foreach ($spreadsheets as $spreadsheet) {
            self::assertSame('Evidence plateb', $spreadsheet->getActiveSheet()->getCell('D2')->getValue());
        }
    }

    public function testChitsExportSumsIncomeAndExpenseSeparately(): void
    {
        $sheet = $this->service->getChitsExport(ExcelServiceFixtures::chits())->getActiveSheet();

        self::assertSame('Doklady', $sheet->getTitle());
        self::assertSame(1, $sheet->getCell('A2')->getValue());
        self::assertSame('10.07.2026', $sheet->getCell('B2')->getValue());
        self::assertSame(500.0, $sheet->getCell('F2')->getValue());
        self::assertSame('Příjem', $sheet->getCell('G2')->getValue());
        self::assertSame('Výdaj', $sheet->getCell('G3')->getValue());

        // Součtové řádky navazují na tabulku (2 doklady = řádky 2 a 3).
        self::assertSame('Příjmy', $sheet->getCell('E5')->getValue());
        self::assertSame(500.0, $sheet->getCell('F5')->getValue());
        self::assertSame('Výdaje', $sheet->getCell('E6')->getValue());
        self::assertSame(120.0, $sheet->getCell('F6')->getValue());

        self::assertSame(
            Border::BORDER_THIN,
            $sheet->getStyle('A1')->getBorders()->getTop()->getBorderStyle(),
        );
    }

    public function testChitsExportWithoutIncomeSkipsIncomeSummary(): void
    {
        $sheet = $this->service->getChitsExport([ExcelServiceFixtures::expenseChit()])->getActiveSheet();

        self::assertSame('Výdaje', $sheet->getCell('E4')->getValue());
        self::assertSame(120.0, $sheet->getCell('F4')->getValue());
        self::assertNull($sheet->getCell('E5')->getValue(), 'bez příjmů se řádek Příjmy nepřidá');
    }

    public function testItemsExportListsEveryChitItemOnItsOwnRow(): void
    {
        $sheet = $this->service->addItemsExport(new Spreadsheet(), ExcelServiceFixtures::chits())->getActiveSheet();

        self::assertSame('Položky z dokladů', $sheet->getTitle());
        self::assertSame('Kategorie', $sheet->getCell('E1')->getValue());

        self::assertSame('účastnické poplatky', $sheet->getCell('D2')->getValue());
        self::assertSame('Přijmy od účastníků', $sheet->getCell('E2')->getValue());
        self::assertSame(500.0, $sheet->getCell('G2')->getValue());

        self::assertSame('nákup potravin', $sheet->getCell('D3')->getValue());
        self::assertSame(70.0, $sheet->getCell('G3')->getValue());
        self::assertSame('kancelář', $sheet->getCell('D4')->getValue());
        self::assertSame(50.0, $sheet->getCell('G4')->getValue(), 'druhá položka výdajového dokladu');
    }

    public function testPaymentsListSheetTranslatesStateAndIsNamedAfterGroup(): void
    {
        $sheet = $this->service->getPaymentsList(
            [
                ExcelServiceFixtures::payment(1, 'Jan Novák', 300.0, State::get(State::PREPARING)),
                ExcelServiceFixtures::payment(2, 'Eva Malá', 450.0, State::get(State::COMPLETED)),
            ],
            'Oddílové příspěvky',
        )->getActiveSheet();

        self::assertSame('Oddílové příspěvky', $sheet->getTitle());
        self::assertSame('Název/účel', $sheet->getCell('B1')->getValue());

        self::assertSame('Jan Novák', $sheet->getCell('B2')->getValue());
        self::assertSame('jan1@example.com', $sheet->getCell('C2')->getValue());
        self::assertSame(300.0, $sheet->getCell('D2')->getValue());
        self::assertSame(101, $sheet->getCell('E2')->getValue(), 'variabilní symbol jako číslo');
        self::assertSame('15.08.2026', $sheet->getCell('F2')->getValue());
        self::assertSame('Nezaplacena', $sheet->getCell('G2')->getValue());
        self::assertSame('Dokončena', $sheet->getCell('G3')->getValue());
    }

    public function testSpreadsheetCarriesApplicationMetadata(): void
    {
        $properties = $this->service->getCampParticipants([])->getProperties();

        self::assertSame('h.skauting.cz', $properties->getCreator());
        self::assertSame('h.skauting.cz', $properties->getLastModifiedBy());
    }
}

final class ExcelServiceFixtures
{
    /** @return Chit[] */
    public static function chits(): array
    {
        return [self::incomeChit(), self::expenseChit()];
    }

    public static function incomeChit(): Chit
    {
        return new Chit(
            1,
            new ChitBody(new ChitNumber('1'), new ChronosDate('2026-07-10'), new Recipient('Jan Novák')),
            false,
            [],
            PaymentMethod::CASH(),
            [new ChitItem(new Amount('500'), self::category(1, 'Přijmy od účastníků', Operation::INCOME()), 'účastnické poplatky')],
            Operation::INCOME(),
            new Amount('500'),
            [],
        );
    }

    public static function expenseChit(): Chit
    {
        $category = self::category(2, 'Potraviny', Operation::EXPENSE());

        return new Chit(
            2,
            new ChitBody(new ChitNumber('2'), new ChronosDate('2026-07-11'), new Recipient('Eva Malá')),
            false,
            [],
            PaymentMethod::CASH(),
            [
                new ChitItem(new Amount('70'), $category, 'nákup potravin'),
                new ChitItem(new Amount('50'), $category, 'kancelář'),
            ],
            Operation::EXPENSE(),
            new Amount('120'),
            [],
        );
    }

    public static function category(int $id, string $name, Operation $operation): Category
    {
        return new Category($id, $name, 'sc'.$id, $operation, false);
    }

    public static function participant(
        int $id,
        string $firstName,
        string $lastName,
        ?ChronosDate $birthday,
        float $payment,
        float $repayment,
        int $days,
        ?int $age = null,
        string $onAccount = 'N',
    ): Participant {
        return new Participant(
            $id,
            100 + $id,
            $firstName,
            $lastName,
            'Přezdívka'.$id,
            $age,
            $birthday,
            'Ulice '.$id,
            'Praha',
            11000,
            'accepted',
            'Oddíl',
            '123.45',
            $days,
            true,
            new Payment(
                PaymentId::generate(),
                $id,
                new Event(42, EventType::GENERAL()),
                MoneyFactory::fromFloat($payment),
                MoneyFactory::fromFloat($repayment),
                $onAccount,
            ),
            null,
        );
    }

    public static function payment(int $id, string $name, float $amount, State $state): PaymentDto
    {
        return new PaymentDto(
            $id,
            $name,
            $amount,
            [new EmailAddress('jan'.$id.'@example.com')],
            new ChronosDate('2026-08-15'),
            new VariableSymbol('10'.$id),
            null,
            '',
            false,
            $state,
            null,
            null,
            null,
            null,
            10,
            [],
        );
    }
}

final class ExcelServiceQueryBusStub implements QueryBus
{
    public function handle(object $query): mixed
    {
        if ($query instanceof CashbookQuery) {
            return new Cashbook(
                CashbookId::generate(),
                CashbookType::get(CashbookType::EVENT),
                'H',
                'B',
                '',
                true,
                true,
            );
        }

        if ($query instanceof CategoryListQuery) {
            return [
                ExcelServiceFixtures::category(1, 'Přijmy od účastníků', Operation::INCOME()),
                ExcelServiceFixtures::category(2, 'Potraviny', Operation::EXPENSE()),
            ];
        }

        if ($query instanceof ChitListQuery) {
            return ExcelServiceFixtures::chits();
        }

        return [];
    }
}
