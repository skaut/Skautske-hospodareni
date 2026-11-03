<?php

declare(strict_types=1);

namespace App\Presentation\Settings\Automation;

final class AutomationPresenter extends \App\Presentation\Settings\SettingsBasePresenter
{
    /** Keep in sync with the jobs in scheduler.neon. */
    private const SCHEDULED_JOBS = [
        [
            'name' => 'Párování plateb',
            'cron' => '0 */2 * * *',
            'description' => 'Páruje bankovní transakce s platbami ve skupinách, kde je zapnuté automatické párování.',
            'interval' => 'každé 2 hodiny',
        ],
    ];

    public function renderDefault(): void
    {
        $this->setSettingsTemplateParameters();
        $this->template->setParameters([
            'jobs' => self::SCHEDULED_JOBS,
        ]);
    }
}
