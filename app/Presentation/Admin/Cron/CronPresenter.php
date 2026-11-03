<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Cron;

use App\Model\Cron\CronInterval;
use App\Model\Cron\Manager\CronJobManager;
use App\Model\Cron\ReadModel\Queries\CronJobsQuery;
use App\Model\DTO\Cron\CronJob;
use App\Presentation\Admin\AdminBasePresenter;
use Component\Forms\BaseForm;
use DateTimeImmutable;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Http\IResponse;

use function is_string;

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
     * The offered intervals; a default or current schedule outside of them stays selectable as well.
     *
     * @return array<string, string>
     */
    private function getScheduleItems(CronJob $job): array
    {
        $items = [];

        foreach ([$job->defaultSchedule, $job->schedule] as $schedule) {
            if (CronInterval::tryFrom($schedule) === null) {
                $items[$schedule] = $schedule;
            }
        }

        foreach (CronInterval::cases() as $interval) {
            $items[$interval->value] = $interval->getLabel();
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
}
