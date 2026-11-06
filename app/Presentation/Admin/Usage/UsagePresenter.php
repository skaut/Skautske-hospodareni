<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Usage;

use App\Model\Common\Services\KeyedHasher;
use App\Model\Stat\ReadModel\Queries\UsageStatisticsQuery;
use App\Model\Usage\ReadModel\Queries\SystemUsageQuery;
use App\Model\Usage\UsageSection;
use App\Model\Usage\UsageUnitCode;
use App\Presentation\Admin\AdminBasePresenter;
use Component\Forms\BaseForm;
use Nette\Application\BadRequestException;
use Nette\Forms\Form;
use Nette\Http\IResponse;
use Nette\Utils\Strings;

use function date;

/**
 * Usage of the application itself, kept apart from Admin:Statistics on purpose: that page answers
 * "what is in the system", this one answers "which parts of it are used and where".
 *
 * The administrator always sees the whole system, the role in skautIS plays no part. The first level is the
 * whole system with its kraje, the second one kraj with its okresy, the third one okres with its střediska.
 * It loads out of the database alone.
 */
final class UsagePresenter extends AdminBasePresenter
{
    public const PERIOD_YEAR = 'year';
    public const PERIOD_MONTH = 'month';
    public const PERIOD_MONTHS = 'months';

    public const MONTH_NAMES = [
        1 => 'leden',
        2 => 'únor',
        3 => 'březen',
        4 => 'duben',
        5 => 'květen',
        6 => 'červen',
        7 => 'červenec',
        8 => 'srpen',
        9 => 'září',
        10 => 'říjen',
        11 => 'listopad',
        12 => 'prosinec',
    ];

    /** Sessions and logins are counted since 2026, nothing older exists. */
    private const FIRST_YEAR = 2026;

    private const PERIODS = [
        self::PERIOD_YEAR => 'Celý rok',
        self::PERIOD_MONTH => 'Jeden měsíc',
        self::PERIOD_MONTHS => 'Rok po měsících',
    ];

    private int $year;

    private string $period = self::PERIOD_YEAR;

    private int $month;

    private ?string $region = null;

    private ?string $district = null;

    private ?UsageSection $section = null;

    public function __construct(private readonly KeyedHasher $hasher)
    {
    }

    public function actionDefault(
        ?int $year = null,
        string $period = self::PERIOD_YEAR,
        ?int $month = null,
        ?string $region = null,
        ?string $district = null,
        ?string $section = null,
    ): void {
        $currentYear = (int) date('Y');
        $this->year = $year ?? $currentYear;
        $this->month = $month ?? ($this->year === $currentYear ? (int) date('n') : 1);

        if (
            $this->year < self::FIRST_YEAR
            || $this->year > $currentYear
            || ! isset(self::PERIODS[$period])
            || ! isset(self::MONTH_NAMES[$this->month])
            || ($region !== null && ! UsageUnitCode::isRegion($region))
            || ($district !== null && ! UsageUnitCode::isDistrict($district))
            || ($region !== null && $district !== null && UsageUnitCode::regionOf($district) !== $region)
            || ($section !== null && UsageSection::tryFrom($section) === null)
        ) {
            throw new BadRequestException('Unknown usage view.', IResponse::S404_NotFound);
        }

        $this->period = $period;
        $this->district = $district;
        $this->region = $region ?? ($district !== null && UsageUnitCode::isRegion(UsageUnitCode::regionOf($district)) ? UsageUnitCode::regionOf($district) : null);
        $this->section = $section === null ? null : UsageSection::from($section);
    }

    public function renderDefault(): void
    {
        $month = $this->period === self::PERIOD_MONTH ? $this->month : null;

        $this->template->setParameters([
            'adminSection' => 'usage',
            'year' => $this->year,
            'period' => $this->period,
            'month' => $this->month,
            'monthNames' => self::MONTH_NAMES,
            'region' => $this->region,
            'district' => $this->district,
            'breadcrumbs' => $this->breadcrumbs(),
            'section' => $this->section,
            'sectionColumns' => UsageSection::cases(),
            'loginTrackingConfigured' => $this->hasher->isConfigured(),
            'usage' => $this->queryBus->handle(new SystemUsageQuery($this->year, $month, $this->region, $this->district)),
            'overview' => $this->region === null && $this->district === null
                ? $this->queryBus->handle(new UsageStatisticsQuery($this->year, $month))
                : null,
        ]);
    }

    protected function createComponentFilterForm(): BaseForm
    {
        $years = [];
        for ($year = (int) date('Y'); $year >= self::FIRST_YEAR; --$year) {
            $years[$year] = (string) $year;
        }

        $months = [];
        foreach (self::MONTH_NAMES as $number => $name) {
            $months[$number] = Strings::firstUpper($name);
        }

        $sections = [];
        foreach (UsageSection::cases() as $section) {
            $sections[$section->value] = $section->getLabel();
        }

        $form = new BaseForm();
        $form->addSelect('year', 'Rok', $years)
            ->setDefaultValue($this->year);
        $period = $form->addSelect('period', 'Zobrazení', self::PERIODS)
            ->setDefaultValue($this->period);
        $form->addSelect('month', 'Měsíc', $months)
            ->setDefaultValue($this->month);
        $form->addSelect('section', 'Sekce v tabulce', $sections)
            ->setPrompt('Všechny sekce')
            ->setDefaultValue($this->section?->value);
        $period->addCondition(Form::Equal, self::PERIOD_MONTH)
            ->toggle('usage-filter-month');
        $period->addCondition(Form::Equal, self::PERIOD_MONTHS)
            ->toggle('usage-filter-section');
        $form->addSubmit('submit', 'Zobrazit');

        $form->onSuccess[] = function (BaseForm $form): void {
            /** @var array{year: int|string, period: string, month: int|string, section: string|null} $values */
            $values = $form->getValues('array');
            $period = $values['period'];

            $this->redirect('this', [
                'year' => (int) $values['year'],
                'period' => $period,
                'month' => $period === self::PERIOD_MONTH ? (int) $values['month'] : null,
                'region' => $this->region,
                'district' => $this->district,
                'section' => $period === self::PERIOD_MONTHS ? $values['section'] : null,
            ]);
        };

        return $form;
    }

    /**
     * Where the overview is: the whole system, a kraj, an okres. The last one is the current level.
     *
     * @return list<array{label: string, region: string|null, district: string|null}>
     */
    private function breadcrumbs(): array
    {
        $breadcrumbs = [['label' => 'Celý systém', 'region' => null, 'district' => null]];

        if ($this->region !== null) {
            $breadcrumbs[] = ['label' => UsageUnitCode::regionLabel($this->region), 'region' => $this->region, 'district' => null];
        }

        if ($this->district !== null) {
            $breadcrumbs[] = [
                'label' => match (true) {
                    $this->district === UsageUnitCode::SPECIAL => UsageUnitCode::regionLabel($this->district),
                    UsageUnitCode::isRegion($this->district) => 'Kraj a jeho přímé jednotky',
                    default => 'Okres '.$this->district,
                },
                'region' => $this->region,
                'district' => $this->district,
            ];
        }

        return $breadcrumbs;
    }
}
