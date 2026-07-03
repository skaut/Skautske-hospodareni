<?php

declare(strict_types=1);

namespace App\Model\Export;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\ICategory;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantStatisticsQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookDisplayNameQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookOfficialUnitQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\CategoriesSummaryQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitListQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EducationParticipantListQuery;
use App\Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantStatisticsQuery;
use App\Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\DTO\Cashbook\CategorySummary;
use App\Model\DTO\Cashbook\Chit;
use App\Model\DTO\Participant\Statistics;
use App\Model\Event\Camp;
use App\Model\Event\Education;
use App\Model\Event\EducationCourseParticipationStats;
use App\Model\Event\EducationParticipantParticipationStats;
use App\Model\Event\EducationTerm;
use App\Model\Event\Event;
use App\Model\Event\ReadModel\Queries\CampFunctions;
use App\Model\Event\ReadModel\Queries\CampQuery;
use App\Model\Event\ReadModel\Queries\EducationCourseParticipationStatsQuery;
use App\Model\Event\ReadModel\Queries\EducationFunctions;
use App\Model\Event\ReadModel\Queries\EducationParticipantParticipationStatsQuery;
use App\Model\Event\ReadModel\Queries\EducationQuery;
use App\Model\Event\ReadModel\Queries\EducationTermsQuery;
use App\Model\Event\ReadModel\Queries\EventFunctions;
use App\Model\Event\ReadModel\Queries\EventQuery;
use App\Model\Event\Repositories\IEventRepository;
use App\Model\Event\SkautisCampId;
use App\Model\Event\SkautisEducationId;
use App\Model\Event\SkautisEventId;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Enum\InvoicePaymentType;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Participant\Payment\EventType;
use App\Model\Payment\InvalidBankAccount;
use App\Model\Payment\QrPaymentCode;
use App\Model\Services\TemplateFactory;
use App\Model\Unit\UnitService;
use App\Model\Utils\MoneyFactory;
use Nette\Utils\ArrayHash;
use Throwable;
use UnexpectedValueException;

use function array_column;
use function array_filter;
use function array_map;
use function array_sum;
use function array_values;
use function in_array;
use function is_file;
use function is_float;
use function sprintf;

class ExportService
{
    public const CATEGORY_VIRTUAL = 'virtual';
    public const CATEGORY_REAL = 'real';

    public function __construct(
        private UnitService $units,
        private TemplateFactory $templateFactory,
        private IEventRepository $events,
        private QueryBus $queryBus,
        private InvoiceUnitSettingRepository $invoiceUnitSettings,
        private ?IGoogleRepository $googleRepository = null,
    ) {
    }

    public function getNewPage(): string
    {
        return '<pagebreak type="NEXT-ODD" resetpagenum="1" pagenumstyle="i" suppress="off" />';
    }

    public function getParticipants(int $aid, string $type = EventType::GENERAL): string
    {
        if ($type === EventType::CAMP) {
            $templateFile = __DIR__.'/templates/participantCamp.latte';
            $camp = $this->queryBus->handle(new CampQuery(new SkautisCampId($aid)));
            if (! $camp instanceof Camp) {
                throw new UnexpectedValueException('Expected camp query to return camp.');
            }
            $displayName = $camp->getDisplayName();
            $unitId = $camp->getUnitId();
            $list = $this->queryBus->handle(new CampParticipantListQuery($camp->getId()));
        } elseif ($type === EventType::EDUCATION) {
            $templateFile = __DIR__.'/templates/participantEducation.latte';
            $education = $this->queryBus->handle(new EducationQuery(new SkautisEducationId($aid)));
            if (! $education instanceof Education) {
                throw new UnexpectedValueException('Expected education query to return education.');
            }
            $displayName = $education->getDisplayName();
            $unitId = $education->getUnitId();
            $list = $this->queryBus->handle(new EducationParticipantListQuery($education->getId()));
        } else {
            $templateFile = __DIR__.'/templates/participant.latte';
            $event = $this->queryBus->handle(new EventQuery(new SkautisEventId($aid)));
            if (! $event instanceof Event) {
                throw new UnexpectedValueException('Expected event query to return event.');
            }
            $displayName = $event->getDisplayName();
            $unitId = $event->getUnitId();
            $list = $this->queryBus->handle(new EventParticipantListQuery($event->getId()));
        }

        return $this->templateFactory->create($templateFile, [
            'list' => $list,
            'displayName' => $displayName,
            'unitFullNameWithAddress' => $this->units->getOfficialUnit($unitId->toInt())->getFullDisplayNameWithAddress(),
        ]);
    }

    /**
     * vrací pokladní knihu.
     */
    public function getCashbook(CashbookId $cashbookId, PaymentMethod $paymentMethod): string
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));
        if (! $cashbook instanceof Cashbook) {
            throw new UnexpectedValueException('Expected cashbook query to return cashbook.');
        }

        $header = sprintf(
            '%s - %s',
            $paymentMethod->equals(PaymentMethod::CASH()) ? 'Pokladní kniha' : 'Bankovní transakce',
            $this->queryBus->handle(new CashbookDisplayNameQuery($cashbookId)),
        );

        return $this->templateFactory->create(__DIR__.'/templates/cashbook.latte', [
            'header' => $header,
            'prefix' => $cashbook->getChitNumberPrefix($paymentMethod),
            'chits' => $this->queryBus->handle(ChitListQuery::withMethod($paymentMethod, $cashbookId)),
            'unit' => $this->queryBus->handle(new CashbookOfficialUnitQuery($cashbookId)),
        ]);
    }

    /**
     * vrací seznam dokladů.
     */
    public function getChitlist(CashbookId $cashbookId): string
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $cashbookId));

        return $this->templateFactory->create(__DIR__.'/templates/chitlist.latte', [
            'list' => array_filter($chits, function (Chit $chit): bool {
                return ! $chit->isIncome();
            }),
        ]);
    }

    public function getEventReport(int $skautisEventId): string
    {
        $sums = [
            self::CATEGORY_VIRTUAL => [
                Operation::INCOME => [],
                Operation::EXPENSE => [],
            ],
            self::CATEGORY_REAL => [
                Operation::INCOME => [],
                Operation::EXPENSE => [],
            ],
        ];

        $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($skautisEventId)));
        /** @var CategorySummary[] $categoriesSummary */
        $categoriesSummary = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        foreach ($categoriesSummary as $categorySummary) {
            if (in_array($categorySummary->getId(), [ICategory::CATEGORY_HPD_ID, ICategory::CATEGORY_REFUND_ID], true)) {
                continue;
            }

            $virtual = $categorySummary->isVirtual() ? self::CATEGORY_VIRTUAL : self::CATEGORY_REAL;
            $operation = $categorySummary->getOperationType()->getValue();

            $sums[$virtual][$operation][$categorySummary->getId()] = [
                'amount' => MoneyFactory::toFloat($categorySummary->getTotal()),
                'label' => $categorySummary->getName(),
            ];
        }

        $totalIncome = array_sum(
            array_column($sums[self::CATEGORY_REAL][Operation::INCOME], 'amount'),
        );

        $totalExpense = array_sum(
            array_column($sums[self::CATEGORY_REAL][Operation::EXPENSE], 'amount'),
        );

        $virtualTotalIncome = array_sum(
            array_column($sums[self::CATEGORY_VIRTUAL][Operation::INCOME], 'amount'),
        );

        $virtualTotalExpense = array_sum(
            array_column($sums[self::CATEGORY_VIRTUAL][Operation::EXPENSE], 'amount'),
        );

        $stats = $this->queryBus->handle(new EventParticipantStatisticsQuery(new SkautisEventId($skautisEventId)));
        if (! $stats instanceof Statistics) {
            throw new UnexpectedValueException('Expected event participant statistics query to return statistics.');
        }
        $events = $this->events->find(new SkautisEventId($skautisEventId));
        $functions = $this->queryBus->handle(new EventFunctions(new SkautisEventId($skautisEventId)));

        return $this->templateFactory->create(__DIR__.'/templates/eventReport.latte', [
            'participantsCnt' => $stats->getPersonsCount(),
            'personsDays' => $stats->getPersonDays(),
            'event' => $events,
            'chits' => $sums,
            'functions' => $functions,
            'incomes' => array_values($sums[self::CATEGORY_REAL][Operation::INCOME]),
            'expenses' => array_values($sums[self::CATEGORY_REAL][Operation::EXPENSE]),
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'virtualIncomes' => array_values($sums[self::CATEGORY_VIRTUAL][Operation::INCOME]),
            'virtualExpenses' => array_values($sums[self::CATEGORY_VIRTUAL][Operation::EXPENSE]),
            'virtualTotalIncome' => $virtualTotalIncome,
            'virtualTotalExpense' => $virtualTotalExpense,
        ]);
    }

    public function getCampReport(int $skautisCampId, bool $areTotalsConsistentWithSkautis): string
    {
        $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($skautisCampId)));
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        $total = [
            'income' => MoneyFactory::zero(),
            'expense' => MoneyFactory::zero(),
            'virtualIncome' => MoneyFactory::zero(),
            'virtualExpense' => MoneyFactory::zero(),
        ];

        $incomeCategories = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];
        $expenseCategories = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];

        foreach ($categories as $category) {
            if (! $category instanceof CategorySummary) {
                throw new UnexpectedValueException('Expected categories summary query to return category summaries.');
            }

            $virtualCategory = $category->isVirtual() ? self::CATEGORY_VIRTUAL : self::CATEGORY_REAL;

            if ($category->isIncome()) {
                $key = $category->isVirtual() ? 'virtualIncome' : 'income';
                $total[$key] = $total[$key]->add($category->getTotal());
                $incomeCategories[$virtualCategory][] = $category;
            } else {
                $key = $category->isVirtual() ? 'virtualExpense' : 'expense';
                $total[$key] = $total[$key]->add($category->getTotal());
                $expenseCategories[$virtualCategory][] = $category;
            }
        }

        $stats = $this->queryBus->handle(new CampParticipantStatisticsQuery(new SkautisCampId($skautisCampId)));
        if (! $stats instanceof Statistics) {
            throw new UnexpectedValueException('Expected camp participant statistics query to return statistics.');
        }

        $finalRealBalance = MoneyFactory::toFloat($this->queryBus->handle(new FinalRealBalanceQuery($cashbookId)));
        if (! is_float($finalRealBalance)) {
            throw new UnexpectedValueException('Expected final real balance to be float.');
        }

        return $this->templateFactory->create(__DIR__.'/templates/campReport.latte', [
            'participantsCnt' => $stats->getPersonsCount(),
            'personsDays' => $stats->getPersonDays(),
            'camp' => $this->queryBus->handle(new CampQuery(new SkautisCampId($skautisCampId))),
            'incomeCategories' => $incomeCategories[self::CATEGORY_REAL],
            'expenseCategories' => $expenseCategories[self::CATEGORY_REAL],
            'totalIncome' => $total['income'],
            'totalExpense' => $total['expense'],
            'virtualIncomeCategories' => $incomeCategories[self::CATEGORY_VIRTUAL],
            'virtualExpenseCategories' => $expenseCategories[self::CATEGORY_VIRTUAL],
            'virtualTotalIncome' => $total['virtualIncome'],
            'virtualTotalExpense' => $total['virtualExpense'],
            'functions' => $this->queryBus->handle(new CampFunctions(new SkautisCampId($skautisCampId))),
            'areTotalsConsistentWithSkautis' => $areTotalsConsistentWithSkautis,
            'finalRealBalance' => $finalRealBalance,
        ]);
    }

    public function getEducationReport(SkautisEducationId $educationId, int $year): string
    {
        $cashbookId = $this->queryBus->handle(new EducationCashbookIdQuery($educationId, $year));
        $categories = $this->queryBus->handle(new CategoriesSummaryQuery($cashbookId));

        $total = [
            'income' => MoneyFactory::zero(),
            'expense' => MoneyFactory::zero(),
            'virtualIncome' => MoneyFactory::zero(),
            'virtualExpense' => MoneyFactory::zero(),
        ];

        $incomeCategories = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];
        $expenseCategories = [self::CATEGORY_REAL => [], self::CATEGORY_VIRTUAL => []];

        foreach ($categories as $category) {
            if (! $category instanceof CategorySummary) {
                throw new UnexpectedValueException('Expected categories summary query to return category summaries.');
            }

            $virtualCategory = $category->isVirtual() ? self::CATEGORY_VIRTUAL : self::CATEGORY_REAL;

            if ($category->isIncome()) {
                $key = $category->isVirtual() ? 'virtualIncome' : 'income';
                $total[$key] = $total[$key]->add($category->getTotal());
                $incomeCategories[$virtualCategory][] = $category;
            } else {
                $key = $category->isVirtual() ? 'virtualExpense' : 'expense';
                $total[$key] = $total[$key]->add($category->getTotal());
                $expenseCategories[$virtualCategory][] = $category;
            }
        }

        $finalRealBalance = MoneyFactory::toFloat($this->queryBus->handle(new FinalRealBalanceQuery($cashbookId)));
        if (! is_float($finalRealBalance)) {
            throw new UnexpectedValueException('Expected final real balance to be float.');
        }

        $education = $this->queryBus->handle(new EducationQuery($educationId));
        $terms = $this->queryBus->handle(new EducationTermsQuery($educationId->toInt()));
        $courseParticipationStats = $this->queryBus->handle(new EducationCourseParticipationStatsQuery($educationId->toInt()));
        $participantParticipationStats = $this->queryBus->handle(new EducationParticipantParticipationStatsQuery($education->grantId->toInt()));

        return $this->templateFactory->create(__DIR__.'/templates/educationReport.latte', [
            'education' => $education,
            'totalDays' => EducationTerm::countTotalDays($terms),
            'participantsAccepted' => array_sum(
                array_map(
                    static function (EducationCourseParticipationStats $stat) {
                        return $stat->accepted;
                    },
                    $courseParticipationStats,
                ),
            ),
            'personDaysReal' => array_sum(
                array_map(
                    static function (EducationParticipantParticipationStats $stat) {
                        return $stat->totalDays;
                    },
                    $participantParticipationStats,
                ),
            ),
            'incomeCategories' => $incomeCategories[self::CATEGORY_REAL],
            'expenseCategories' => $expenseCategories[self::CATEGORY_REAL],
            'totalIncome' => $total['income'],
            'totalExpense' => $total['expense'],
            'virtualIncomeCategories' => $incomeCategories[self::CATEGORY_VIRTUAL],
            'virtualExpenseCategories' => $expenseCategories[self::CATEGORY_VIRTUAL],
            'virtualTotalIncome' => $total['virtualIncome'],
            'virtualTotalExpense' => $total['virtualExpense'],
            'functions' => $this->queryBus->handle(new EducationFunctions($educationId)),
            'finalRealBalance' => $finalRealBalance,
        ]);
    }

    public function getInvoice(Invoice $invoice, ?string $stampImageSrc = null, ?string $logoImageSrc = null): string
    {
        $qrPaymentSvg = $this->buildQrPaymentSvg($invoice);

        $data = [
            // Data dodavatele (proměnná {$supplier->...})
            'supplier' => [
                'name' => $invoice->getSupplier()->getName(),
                'street' => $invoice->getSupplier()->getAddress()->getStreet(),
                'city' => $invoice->getSupplier()->getAddress()->getCity(),
                'zip' => $invoice->getSupplier()->getAddress()->getZipCode(),
                'country' => 'Česká republika',
                'ic' => $invoice->getSupplier()->getCompanyNumber(),

                'mobil' => $invoice->getSupplier()->getPhone() ?? $invoice->getSequence()->getPhone(),
                'email' => $this->resolveSenderEmail($invoice),

                'bankName' => $invoice->getBankName(),
                'bankAccount' => $invoice->getAccountNumber()?->getNumberWithPrefixAndBankCode(),
                'iban' => $invoice->getIban(),
                'bic' => $invoice->getBic(),
            ],

            // Data odběratele (proměnná {$customer->...})
            'customer' => [
                'name' => $invoice->getCustomer()->getName(),
                'address' => $invoice->getCustomer()->getDisplayAddress(),
                'ic' => $invoice->getCustomer()->getCompanyNumber(),
                'dic' => $invoice->getCustomer()->getVatNumber(),
                'hasCompanyNumber' => $invoice->getCustomer()->hasCompanyNumber(),
                'hasVatNumber' => $invoice->getCustomer()->hasVatNumber(),
                'isAnonymous' => $invoice->getCustomer()->isAnonymous(),
            ],

            // Data faktury (proměnná {$invoice->...})
            'invoice' => [
                'number' => $invoice->getInvoiceNumber(),
                'variableSymbol' => $invoice->getVariableSymbol(),
                'constantSymbol' => '0008',
                'specificSymbol' => '',
                'paymentMethod' => $invoice->getPaymentType(),

                'dateIssued' => $invoice->getDateOfIssue(),
                'dateDue' => $invoice->getDueDate(),

                'items' => $invoice->getItems(),

                // Celkové částky
                'totalAmount' => $invoice->getTotalAmount(),
                'deposits' => 0.00, // [cite: 38]
                'amountDue' => $invoice->getTotalAmount(),
            ],
            'qrPaymentSvg' => $qrPaymentSvg,
            'stampImagePath' => $stampImageSrc ?? $this->getInvoiceStampImagePath($invoice),
            'logoImagePath' => $logoImageSrc ?? $this->getInvoiceLogoImagePath($invoice),
            'user' => [
                'name' => $invoice->getIssuedBy(),
            ],
        ];

        return $this->templateFactory->create(__DIR__.'/templates/invoice.latte', (array) ArrayHash::from($data));
    }

    private function buildQrPaymentSvg(Invoice $invoice): ?string
    {
        if ($invoice->getPaymentType()->value !== InvoicePaymentType::TRANSFER->value) {
            return null;
        }

        $bankAccount = $invoice->getAccountNumber()?->getNumberWithPrefixAndBankCode();
        if ($bankAccount === null) {
            return null;
        }

        try {
            return QrPaymentCode::buildSvgWithCaption(
                $bankAccount,
                (string) $invoice->getTotalAmount(),
                $invoice->getVariableSymbol()->toInt(),
                8,
                $invoice->getInvoiceNumber(),
                'QR platba',
                86,
            );
        } catch (InvalidBankAccount) {
            return null;
        }
    }

    public function getInvoiceStampImagePath(Invoice $invoice): ?string
    {
        return $this->getInvoiceSettingImagePath($invoice, 'stamp');
    }

    public function getInvoiceLogoImagePath(Invoice $invoice): ?string
    {
        return $this->getInvoiceSettingImagePath($invoice, 'logo');
    }

    private function getInvoiceSettingImagePath(Invoice $invoice, string $type): ?string
    {
        $unitSetting = $this->invoiceUnitSettings->findByUnitAndYear(
            $invoice->getSequence()->getUnit(),
            (int) $invoice->getDateOfIssue()->format('Y'),
        );

        $imagePath = $type === 'logo'
            ? $unitSetting?->getLogoImagePath()
            : $unitSetting?->getStampImagePath();

        if ($imagePath === null || ! is_file($imagePath)) {
            return null;
        }

        return $imagePath;
    }

    private function resolveSenderEmail(Invoice $invoice): ?string
    {
        $oauthId = $invoice->getSequence()->getOauthId();
        if ($oauthId === null || $this->googleRepository === null) {
            return null;
        }

        try {
            return $this->googleRepository->find($oauthId)->getEmail();
        } catch (Throwable) {
            return null;
        }
    }
}
