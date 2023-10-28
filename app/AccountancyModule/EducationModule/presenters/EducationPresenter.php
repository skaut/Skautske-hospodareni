<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use Model\Auth\Resources\Education;
use Model\Auth\Resources\Grant;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\MissingCategory;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\EducationLocation;
use Model\Event\EducationTerm;
use Model\Event\ReadModel\Queries\EducationCourseParticipationStatsQuery;
use Model\Event\ReadModel\Queries\EducationCoursesQuery;
use Model\Event\ReadModel\Queries\EducationFunctions;
use Model\Event\ReadModel\Queries\EducationInstructorsQuery;
use Model\Event\ReadModel\Queries\EducationLocationsQuery;
use Model\Event\ReadModel\Queries\EducationParticipantParticipationStatsQuery;
use Model\Event\ReadModel\Queries\EducationTermsQuery;
use Model\Event\SkautisEducationId;
use Model\ExportService;
use Model\Grant\ReadModel\Queries\GrantQuery;
use Model\Services\PdfRenderer;

use function array_filter;
use function array_map;
use function array_sum;
use function assert;
use function count;
use function implode;
use function in_array;

class EducationPresenter extends BasePresenter
{
    public function __construct(
        protected ExportService $exportService,
        private PdfRenderer $pdf,
    ) {
        parent::__construct();
    }

    public function renderDefault(int|null $aid): void
    {
        if ($aid === null) {
            $this->redirect('Default:');
        }

        $cashbook = $this->event->startDate !== null
            ? $this->queryBus->handle(new CashbookQuery($this->getCashbookId($aid, $this->event->startDate->year)))
            : null;

        $finalRealBalance = null;
        if ($this->event->startDate !== null && $this->authorizator->isAllowed(Education::ACCESS_BUDGET, $aid)) {
            try {
                $finalRealBalance = $this->queryBus->handle(new FinalRealBalanceQuery($this->getCashbookId($aid, $this->event->startDate->year)));
            } catch (MissingCategory) {
            }
        }

        $grant                         = $this->event->grantId !== null && $this->authorizator->isAllowed(Grant::ACCESS_DETAIL, $this->event->grantId->toInt())
            ? $this->queryBus->handle(new GrantQuery($this->event->grantId->toInt()))
            : null;
        $terms                         = $this->queryBus->handle(new EducationTermsQuery($aid));
        $instructors                   = $this->queryBus->handle(new EducationInstructorsQuery($aid));
        $courseParticipationStats      = $this->authorizator->isAllowed(Education::ACCESS_COURSE_PARTICIPANTS, $aid)
            ? $this->queryBus->handle(new EducationCourseParticipationStatsQuery($aid))
            : null;
        $courses                       = $this->queryBus->handle(new EducationCoursesQuery($aid));
        $locations                     = $this->queryBus->handle(new EducationLocationsQuery($aid));
        $participantParticipationStats = $this->event->grantId !== null && $this->authorizator->isAllowed(Grant::ACCESS_PARTICIPANT_PARTICIPATION, $this->event->grantId->toInt())
            ? $this->queryBus->handle(new EducationParticipantParticipationStatsQuery($this->event->grantId->toInt()))
            : null;

        $termLocationIds     = array_map(
            static function (EducationTerm $term) {
                return $term->locationId;
            },
            $terms,
        );
        $locationsUsedInTerm = array_filter(
            $locations,
            static function (EducationLocation $location) use ($termLocationIds) {
                return in_array($location->id, $termLocationIds);
            },
        );

        $this->template->setParameters([
            'skautISUrl'               => $this->userService->getSkautisUrl(),
            'canAccessReport'          => $this->authorizator->isAllowed(Education::ACCESS_DETAIL, $aid) && $this->authorizator->isAllowed(Education::ACCESS_BUDGET, $aid),
            'location'                 => implode(
                ', ',
                array_map(
                    static function (EducationLocation $location) {
                        return $location->name;
                    },
                    $locationsUsedInTerm,
                ),
            ),
            'functions'                => $this->authorizator->isAllowed(Education::ACCESS_FUNCTIONS, $aid)
                ? $this->queryBus->handle(new EducationFunctions(new SkautisEducationId($aid)))
                : null,
            'finalRealBalance'         => $finalRealBalance,
            'prefixCash'               => $cashbook !== null ? $cashbook->getChitNumberPrefix(PaymentMethod::CASH()) : null,
            'prefixBank'               => $cashbook !== null ? $cashbook->getChitNumberPrefix(PaymentMethod::BANK()) : null,
            'totalDays'                => EducationTerm::countTotalDays($terms),
            'teamCount'                => count($instructors),
            'participantsCapacity'     => self::propertySum($courseParticipationStats, 'capacity'),
            'participantsAccepted'     => self::propertySum($courseParticipationStats, 'accepted'),
            'personDaysEstimated'      => self::propertySum($courses, 'estimatedPersonDays'),
            'personDaysReal'           => $participantParticipationStats !== null
                ? self::propertySum($participantParticipationStats, 'totalDays')
                : null,
            'grantState'               => $grant?->state,
            'grantAmountMax'           => $grant?->amountMax,
            'grantAmountPerPersonDays' => $grant?->amountPerPersonDays,
            'grantCostRatio'           => $grant?->costRatio,
        ]);

        if (! $this->isAjax()) {
            return;
        }

        $this->redrawControl('contentSnip');
    }

    public function renderReport(int $aid): void
    {
        $template = $this->exportService->getEducationReport(new SkautisEducationId($aid), $this->event->startDate->year);

        $this->pdf->render($template, 'report.pdf');
        $this->terminate();
    }

    private function getCashbookId(int $skautisEducationId, int $year): CashbookId
    {
        return $this->queryBus->handle(new EducationCashbookIdQuery(new SkautisEducationId($skautisEducationId), $year));
    }

    /**
     * @param array<T>|null $arr
     *
     * @template T
     */
    private static function propertySum(array|null $arr, string $property): int|null
    {
        if ($arr === null) {
            return null;
        }

        $propertyValues = array_filter(
            array_map(
                static function ($item) use ($property) {
                    return $item->$property;
                },
                $arr,
            ),
            static function (int|null $value) {
                return $value !== null;
            },
        );

        return count($propertyValues) > 0 ? array_sum($propertyValues) : null;
    }
}
