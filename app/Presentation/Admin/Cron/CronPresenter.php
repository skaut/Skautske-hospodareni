<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Cron;

use App\Model\Cron\CronInterval;
use App\Model\Cron\Manager\CronJobManager;
use App\Model\Cron\ReadModel\Queries\CronJobsQuery;
use App\Model\Cron\Services\CronJobRegistry;
use App\Model\DTO\Cron\CronJob;
use App\Model\DTO\TaskQueue\TaskJobStatus;
use App\Model\DTO\TaskQueue\TaskTypeSummary;
use App\Model\Payment\ReadModel\Queries\CountAutomaticPairingGroupsQuery;
use App\Model\Payment\ReadModel\Queries\CountAutomaticReminderGroupsQuery;
use App\Model\TaskQueue\ReadModel\Queries\TaskQueueOverviewQuery;
use App\Presentation\Admin\AdminBasePresenter;
use Component\Forms\BaseForm;
use DateTimeImmutable;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Http\IResponse;

use function array_filter;
use function array_map;
use function array_sum;
use function array_unique;
use function count;
use function is_string;
use function sprintf;

/**
 * Overview of the jobs that www/cron.php runs, with pausing and a choice of interval.
 */
final class CronPresenter extends AdminBasePresenter
{
    /** @var array<string, CronJob>|null */
    private ?array $jobs = null;

    public function __construct(private readonly CronJobManager $cronJobManager)
    {
    }

    public function renderDefault(): void
    {
        $this->template->setParameters([
            'jobs' => $this->getJobs(),
            'jobUsage' => $this->getJobUsage(),
        ]);
    }

    public function handlePause(string $name): void
    {
        $job = $this->getJob($name);
        $this->cronJobManager->pause($name, $this->getEditorName());
        $this->flashMessage('Úloha '.$job->title.' je pozastavená, cron ji nebude spouštět.', 'success');
        $this->redirect('default');
    }

    public function handleResume(string $name): void
    {
        $job = $this->getJob($name);
        $this->cronJobManager->resume($name, $this->getEditorName());
        $this->flashMessage('Úloha '.$job->title.' znovu poběží '.$job->getIntervalLabel().'.', 'success');
        $this->redirect('default');
    }

    protected function createComponentScheduleForm(): Multiplier
    {
        return new Multiplier(function (string $name): Form {
            $job = $this->getJob($name);
            $form = new BaseForm();

            $form->addSelect('schedule', 'Interval', $this->getScheduleItems($job))
                ->setDefaultValue($job->schedule)
                ->setHtmlAttribute('data-test', 'admin-cron-schedule-'.$name);
            $form->addSubmit('save', 'Změnit interval')
                ->setHtmlAttribute('class', 'btn btn-outline-secondary')
                ->setHtmlAttribute('data-test', 'admin-cron-schedule-save-'.$name);

            $form->onSuccess[] = function (Form $form, array $values) use ($job): void {
                $schedule = $values['schedule'] ?? null;
                $interval = is_string($schedule) ? CronInterval::tryFrom($schedule) : null;

                $this->cronJobManager->changeSchedule($job->name, $interval, $this->getEditorName());
                $this->flashMessage('Úloha '.$job->title.' poběží '.($interval?->getLabel() ?? $job->defaultSchedule).'.', 'success');
                $this->redirect('default');
            };

            return $form;
        });
    }

    /**
     * The intervals offered for the job; a default or current schedule outside of them stays selectable as well.
     *
     * @return array<string, string>
     */
    private function getScheduleItems(CronJob $job): array
    {
        $items = [];

        foreach (CronInterval::availableFor($job->name) as $interval) {
            $items[$interval->value] = $interval->getLabel();
        }

        foreach ([$job->defaultSchedule, $job->schedule] as $schedule) {
            $items[$schedule] ??= CronInterval::tryFrom($schedule)?->getLabel() ?? $schedule;
        }

        $items[$job->defaultSchedule] .= ' (výchozí)';

        return $items;
    }

    private function getJob(string $name): CronJob
    {
        return $this->getJobs()[$name] ?? throw new BadRequestException('Unknown cron job '.$name, IResponse::S404_NotFound);
    }

    /** @return array<string, CronJob> */
    private function getJobs(): array
    {
        return $this->jobs ??= $this->queryBus->handle(new CronJobsQuery(new DateTimeImmutable()));
    }

    /**
     * What each job works with, so the administrator sees how much it is used. Closed groups are left out.
     *
     * @return array<string, array{value: string, note: string, link: string|null}>
     */
    private function getJobUsage(): array
    {
        $overview = $this->queryBus->handle(new TaskQueueOverviewQuery(new DateTimeImmutable()));
        $waiting = array_sum(array_map(static fn (TaskTypeSummary $type): int => $type->pending + $type->running, $overview->types));
        $activeJobs = array_filter($overview->jobs, static fn (TaskJobStatus $job): bool => ! $job->isFinished());
        $scopes = count(array_unique(array_map(static fn (TaskJobStatus $job): string => $job->scopeType.':'.$job->scopeId, $activeJobs)));

        return [
            CronJobRegistry::PAYMENT_PAIRING => [
                'value' => self::openGroups($this->queryBus->handle(new CountAutomaticPairingGroupsQuery())),
                'note' => 'se zapnutým automatickým párováním a účtem FIO, uzavřené skupiny se nepočítají',
                'link' => null,
            ],
            CronJobRegistry::PAYMENT_REMINDERS => [
                'value' => self::openGroups($this->queryBus->handle(new CountAutomaticReminderGroupsQuery())),
                'note' => 'se zapnutými automatickými upomínkami, zvoleným počtem dní, aktivní šablonou a odesílatelem, uzavřené skupiny se nepočítají',
                'link' => null,
            ],
            CronJobRegistry::TASK_QUEUE => [
                'value' => sprintf('Čeká úloh: %d, skupin se zpracovávanou dávkou: %d', $waiting, $scopes),
                'note' => '',
                'link' => $this->link('TaskQueue:default'),
            ],
        ];
    }

    private static function openGroups(int $count): string
    {
        return match (true) {
            $count === 0 => 'Žádná otevřená skupina',
            $count === 1 => '1 otevřená skupina',
            $count < 5 => $count.' otevřené skupiny',
            default => $count.' otevřených skupin',
        };
    }
}
