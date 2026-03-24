<?php

declare(strict_types=1);

namespace App\Presentation\Settings\Automation;

final class AutomationPresenter extends \App\Presentation\Settings\SettingsBasePresenter
{
    /** @var array<int, array{name: string, cron: string, description: string, interval: string}> */
    private const SCHEDULED_JOBS = [
        [
            'name' => 'Upomínky plateb',
            'cron' => '* * * * *',
            'description' => 'Odesílá e-mailové upomínky za nezaplacené platby ve skupinách s aktivním připomenutím.',
            'interval' => 'každou minutu',
        ],
        [
            'name' => 'Auto-párování faktur',
            'cron' => '*/10 * * * *',
            'description' => 'Páruje bankovní transakce s fakturami v řadách, kde je zapnuté automatické párování.',
            'interval' => 'každých 10 minut',
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
