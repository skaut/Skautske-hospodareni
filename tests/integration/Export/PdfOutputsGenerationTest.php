<?php

declare(strict_types=1);

namespace App\Model\Export;

use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Services\PdfRenderer;
use App\Model\Services\TemplateFactory;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use IntegrationTest;

/**
 * Ověřuje, že se **každý** PDF výstup aplikace skutečně vygeneruje – šablona se přes produkční Latte
 * engine (vč. AccountancyLatteExtension) vyrenderuje a Gotenberg z ní vyrobí PDF (`%PDF-`).
 *
 * Renderuje se přímo šablona s reprezentativními parametry (stejného tvaru, jaký skládá ExportService /
 * ExportChitsHandler), takže test nevyžaduje SkautIS ani DB, ale projde reálnou render cestou a odhalí
 * chybějící/špatně napsané filtry i rozbité šablony (např. `postCode`, `pricetostring`). Faktura má
 * vlastní integrační test v InvoiceMailingServiceTest.
 *
 * Kompletní seznam výstupů je v .docs/vystupy.md.
 */
final class PdfOutputsGenerationTest extends IntegrationTest
{
    private const ExportTemplatesDir = __DIR__.'/../../../app/Model/Export/templates';
    private const ChitTemplatesDir = __DIR__.'/../../../app/Model/Cashbook/ReadModel/QueryHandlers/Pdf/templates';
    private const TravelDir = __DIR__.'/../../../app/Presentation/Travel';

    private TemplateFactory $templates;

    private PdfRenderer $pdf;

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['Export/PdfOutputsGenerationTest.neon']);

        parent::_before();

        $this->templates = $this->tester->grabService(TemplateFactory::class);
        $this->pdf = $this->tester->grabService(PdfRenderer::class);
    }

    public function testParticipantListEvent(): void
    {
        $this->assertGeneratesPdf(self::ExportTemplatesDir.'/participant.latte', $this->participantParams());
    }

    public function testParticipantListCamp(): void
    {
        $this->assertGeneratesPdf(self::ExportTemplatesDir.'/participantCamp.latte', $this->participantParams());
    }

    public function testParticipantListEducation(): void
    {
        $this->assertGeneratesPdf(self::ExportTemplatesDir.'/participantEducation.latte', $this->participantParams());
    }

    public function testCashbook(): void
    {
        $this->assertGeneratesPdf(self::ExportTemplatesDir.'/cashbook.latte', [
            'header' => 'Pokladní kniha - 1. oddíl',
            'prefix' => 'P',
            'chits' => [
                $this->cashbookChit(true, 'P1', 'Startovné', 700.0),
                $this->cashbookChit(false, 'V1', 'Materiál', 120.0),
            ],
            'unit' => $this->officialUnit(),
        ]);
    }

    public function testChitList(): void
    {
        $this->assertGeneratesPdf(self::ExportTemplatesDir.'/chitlist.latte', [
            'list' => [
                $this->cashbookChit(false, 'V1', 'Materiál', 120.0),
                $this->cashbookChit(false, 'V2', 'Doprava', 340.0),
            ],
        ]);
    }

    public function testEventReport(): void
    {
        $this->assertGeneratesPdf(self::ExportTemplatesDir.'/eventReport.latte', $this->eventReportParams());
    }

    public function testCampReport(): void
    {
        $this->assertGeneratesPdf(self::ExportTemplatesDir.'/campReport.latte', $this->campReportParams());
    }

    public function testEducationReport(): void
    {
        $this->assertGeneratesPdf(self::ExportTemplatesDir.'/educationReport.latte', $this->educationReportParams());
    }

    public function testChits(): void
    {
        $this->assertGeneratesPdf(self::ChitTemplatesDir.'/chits.latte', [
            'officialName' => 'Junák – český skaut, 1. oddíl Kopřivnice',
            'cashbook' => $this->cashbook(),
            'pokladnik' => 'Jan Novák',
            'totalPayment' => 500.0,
            'list' => [
                $this->recipient('Petr Svoboda', 100.0),
                $this->recipient('Marie Nováková', 400.0),
            ],
            'income' => [
                $this->chit(false, 'P1', 300.0),
                $this->chit(true, 'P2', 500.0),
            ],
            'outcome' => [
                $this->chit(false, 'V1', 120.0),
            ],
        ]);
    }

    public function testTravelCommand(): void
    {
        $this->assertGeneratesPdf(self::TravelDir.'/Command/ex.command.latte', [
            'baseUrl' => '',
            'command' => (object) [
                'passenger' => (object) ['name' => 'Jan Novák', 'address' => 'Dlouhá 12, Brno', 'contact' => '+420 123 456 789'],
                'unit' => '1. oddíl Kopřivnice',
                'purpose' => 'Táborová porada',
                'place' => 'Louňovice',
                'fellowPassengers' => 'Petr Svoboda',
            ],
            'types' => ['auto', 'vlak'],
            'vehicle' => null,
            'travels' => [],
            'start' => null,
            'end' => null,
        ]);
    }

    public function testTravelContractOld(): void
    {
        $this->assertGeneratesPdf(self::TravelDir.'/Contract/ex.contract.old.latte', $this->contractParams());
    }

    public function testTravelContractNoz(): void
    {
        $this->assertGeneratesPdf(self::TravelDir.'/Contract/ex.contract.noz.latte', $this->contractParams());
    }

    /** @param mixed[] $params */
    private function assertGeneratesPdf(string $template, array $params): void
    {
        $html = $this->templates->create($template, $params);

        self::assertNotSame('', $html);
        self::assertStringStartsWith('%PDF-', $this->pdf->renderToString($html));
    }

    /** @return mixed[] */
    private function participantParams(): array
    {
        return [
            'displayName' => 'Velká zkouška 2022',
            'unitFullNameWithAddress' => 'Junák – český skaut, 1. oddíl Kopřivnice, Křižíkova 12, Praha',
            'list' => [
                $this->participant('Jan', 'Novák'),
                $this->participant('Marie', 'Nováková'),
            ],
        ];
    }

    private function participant(string $firstName, string $lastName): object
    {
        return new class($firstName, $lastName) {
            public string $displayName;

            public string $street = 'Dlouhá 12';

            public string $city = 'Brno';

            public string $postcode = '60200';

            public ?DateTimeImmutable $birthday;

            public float $payment = 500.0;

            public int $age = 12;

            public int $days = 21;

            public string $onAccount = 'Y';

            public float $repayment = 0.0;

            public function __construct(public string $firstName, public string $lastName)
            {
                $this->displayName = $lastName.' '.$firstName;
                $this->birthday = new DateTimeImmutable('2010-05-01');
            }
        };
    }

    private function cashbookChit(bool $income, string $number, string $purpose, float $amount): object
    {
        return new class($income, $number, $purpose, $amount) {
            public object $amount;

            public DateTimeImmutable $date;

            public string $categoriesShortcut = 'A';

            public function __construct(private bool $income, public string $number, public string $purpose, float $amount)
            {
                $this->amount = PdfOutputsGenerationTest::moneyOf($amount);
                $this->date = new DateTimeImmutable('2022-08-06');
            }

            public function isIncome(): bool
            {
                return $this->income;
            }
        };
    }

    private function chit(bool $hpd, string $number, float $amount): object
    {
        return new class($hpd, $number, $amount) {
            public object $amount;

            public DateTimeImmutable $date;

            public string $recipient = 'Petr Svoboda';

            public string $purpose = 'Nákup materiálu na tábor';

            public string $categories = 'Materiál';

            /** @var object[] */
            public array $items;

            public function __construct(private bool $hpd, public string $number, float $amount)
            {
                $this->amount = PdfOutputsGenerationTest::moneyOf($amount);
                $this->date = new DateTimeImmutable('2022-08-06');
                $this->items = [
                    PdfOutputsGenerationTest::chitItem('Materiál', 'Stany', $amount / 2),
                    PdfOutputsGenerationTest::chitItem('Doprava', 'Autobus', $amount / 2),
                ];
            }

            public function isHpd(): bool
            {
                return $this->hpd;
            }

            public function getPaymentMethod(): PaymentMethod
            {
                return PaymentMethod::CASH();
            }
        };
    }

    public static function chitItem(string $category, string $purpose, float $amount): object
    {
        return new class($category, $purpose, $amount) {
            public object $category;

            public object $amount;

            public function __construct(string $category, public string $purpose, float $amount)
            {
                $this->category = (object) ['name' => $category];
                $this->amount = PdfOutputsGenerationTest::moneyOf($amount);
            }
        };
    }

    public static function moneyOf(float $value): object
    {
        return new class($value) {
            public ?string $expression = null;

            public function __construct(private float $value)
            {
            }

            public function toFloat(): float
            {
                return $this->value;
            }

            public function isUsingFormula(): bool
            {
                return $this->expression !== null;
            }
        };
    }

    private function recipient(string $displayName, float $payment): object
    {
        return (object) ['displayName' => $displayName, 'payment' => $payment];
    }

    private function cashbook(): object
    {
        return new class {
            public function getChitNumberPrefix(PaymentMethod $method): string
            {
                return 'P';
            }
        };
    }

    private function officialUnit(): object
    {
        return new class {
            public function getFullDisplayNameWithAddress(): string
            {
                return 'Junák – český skaut, 1. oddíl Kopřivnice, Křižíkova 12, Praha';
            }
        };
    }

    private function person(string $name): object
    {
        return (object) ['name' => $name, 'email' => 'test@example.test'];
    }

    /** @return mixed[] */
    private function eventReportParams(): array
    {
        return [
            'participantsCnt' => 12,
            'personsDays' => 84,
            'event' => (object) [
                'displayName' => 'Velká zkouška 2022',
                'unitName' => '1. oddíl Kopřivnice',
                'registrationNumber' => '411.01',
                'startDate' => new ChronosDate('2022-08-06'),
                'endDate' => new ChronosDate('2022-08-27'),
                'totalDays' => 22,
                'location' => 'Louňovice',
            ],
            'functions' => (object) [
                'leader' => $this->person('Vedoucí'),
                'assistant' => $this->person('Zástupce'),
                'accountant' => $this->person('Hospodář'),
                'medic' => null,
            ],
            'chits' => [],
            'incomes' => [['label' => 'Od účastníků', 'amount' => 700.0]],
            'expenses' => [['label' => 'Materiál', 'amount' => 120.0]],
            'totalIncome' => 700.0,
            'totalExpense' => 120.0,
            'virtualIncomes' => [['label' => 'Převod z pokladny', 'amount' => 200.0]],
            'virtualExpenses' => [['label' => 'Převod do pokladny', 'amount' => 150.0]],
            'virtualTotalIncome' => 200.0,
            'virtualTotalExpense' => 150.0,
        ];
    }

    /** @return mixed[] */
    private function campReportParams(): array
    {
        return [
            'participantsCnt' => 12,
            'personsDays' => 84,
            'camp' => new class {
                public function getDisplayName(): string
                {
                    return 'Velká zkouška 2022';
                }

                public function getUnitName(): string
                {
                    return '1. oddíl Kopřivnice';
                }

                public function getRegistrationNumber(): string
                {
                    return '411.01';
                }

                public function getStartDate(): ChronosDate
                {
                    return new ChronosDate('2022-08-06');
                }

                public function getEndDate(): ChronosDate
                {
                    return new ChronosDate('2022-08-27');
                }

                public function getTotalDays(): int
                {
                    return 22;
                }

                public function getLocation(): string
                {
                    return 'Louňovice';
                }
            },
            'functions' => (object) [
                'leader' => $this->person('Vedoucí'),
                'assistant' => $this->person('Zástupce'),
                'accountant' => $this->person('Hospodář'),
                'medic' => null,
            ],
            'incomeCategories' => [$this->category('Od účastníků', 700.0)],
            'expenseCategories' => [$this->category('Materiál', 120.0)],
            'totalIncome' => 700.0,
            'totalExpense' => 120.0,
            'virtualIncomeCategories' => [$this->category('Převod z pokladny', 200.0)],
            'virtualExpenseCategories' => [$this->category('Převod do pokladny', 150.0)],
            'virtualTotalIncome' => 200.0,
            'virtualTotalExpense' => 150.0,
            'areTotalsConsistentWithSkautis' => true,
            'finalRealBalance' => 580.0,
        ];
    }

    /** @return mixed[] */
    private function educationReportParams(): array
    {
        return [
            'education' => new class {
                public function getDisplayName(): string
                {
                    return 'Čekatelský kurz 2022';
                }

                public function getUnitName(): string
                {
                    return '1. oddíl Kopřivnice';
                }

                public function getUnitRegistrationNumber(): string
                {
                    return '411.01';
                }

                public function getStartDate(): ChronosDate
                {
                    return new ChronosDate('2022-08-06');
                }

                public function getEndDate(): ChronosDate
                {
                    return new ChronosDate('2022-08-27');
                }

                public function getLocation(): string
                {
                    return 'Louňovice';
                }
            },
            'functions' => (object) [
                'leader' => $this->person('Vedoucí'),
                'assistants' => [$this->person('Zástupce 1'), $this->person('Zástupce 2')],
                'accountant' => $this->person('Hospodář'),
                'secretary' => $this->person('Tajemník'),
                'medic' => null,
            ],
            'totalDays' => 22,
            'participantsAccepted' => 12,
            'personDaysReal' => 84,
            'incomeCategories' => [$this->category('Od účastníků', 700.0)],
            'expenseCategories' => [$this->category('Materiál', 120.0)],
            'totalIncome' => 700.0,
            'totalExpense' => 120.0,
            'virtualIncomeCategories' => [$this->category('Převod z pokladny', 200.0)],
            'virtualExpenseCategories' => [$this->category('Převod do pokladny', 150.0)],
            'virtualTotalIncome' => 200.0,
            'virtualTotalExpense' => 150.0,
            'finalRealBalance' => 580.0,
        ];
    }

    private function category(string $name, float $total): object
    {
        return (object) ['name' => $name, 'total' => $total];
    }

    /** @return mixed[] */
    private function contractParams(): array
    {
        return [
            'unit' => new class {
                public function getDisplayName(): string
                {
                    return '1. oddíl Kopřivnice';
                }

                public function getFullDisplayName(): string
                {
                    return 'Junák – český skaut, 1. oddíl Kopřivnice';
                }

                public function getAddress(): string
                {
                    return 'Křižíkova 12, Praha';
                }

                public function getIC(): string
                {
                    return '12345678';
                }
            },
            'contract' => (object) [
                'unitRepresentative' => 'Jan Novák',
                'passenger' => (object) [
                    'name' => 'Petr Svoboda',
                    'address' => 'Dlouhá 12, Brno',
                    'birthday' => new ChronosDate('1990-01-01'),
                ],
                'since' => new ChronosDate('2022-08-06'),
            ],
        ];
    }
}
