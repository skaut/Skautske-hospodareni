<?php

declare(strict_types=1);

namespace App\Presentation\Settings\Automation;

use App\Model\Cron\ReadModel\Queries\CronJobsQuery;
use DateTimeImmutable;

final class AutomationPresenter extends \App\Presentation\Settings\SettingsBasePresenter
{
    public function renderDefault(): void
    {
        $this->setSettingsTemplateParameters();
        $this->template->setParameters([
            'jobs' => $this->queryBus->handle(new CronJobsQuery(new DateTimeImmutable())),
        ]);
    }
}
