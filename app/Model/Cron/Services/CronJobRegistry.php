<?php

declare(strict_types=1);

namespace App\Model\Cron\Services;

use App\Model\Cron\Entity\CronJobState;
use Contributte\Scheduler\ExpressionJob;
use Contributte\Scheduler\IScheduler;
use Cron\CronExpression;
use LogicException;

use function array_key_exists;
use function sprintf;

/**
 * The jobs configured in scheduler.neon together with their wording for people.
 */
final class CronJobRegistry
{
    public const PAYMENT_PAIRING = 'paymentPairing';
    public const PAYMENT_REMINDERS = 'paymentReminders';
    public const TASK_QUEUE = 'taskQueue';

    /** Keep in sync with the jobs in scheduler.neon. */
    private const DESCRIPTIONS = [
        self::PAYMENT_PAIRING => [
            'title' => 'Párování plateb',
            'description' => 'Páruje transakce z účtů napojených na FIO s platbami ve skupinách, kde je zapnuté automatické párování.',
        ],
        self::PAYMENT_REMINDERS => [
            'title' => 'Automatické upomínky plateb',
            'description' => 'Zařadí k odeslání upomínky nezaplacených plateb ve skupinách, kde je zapnuté automatické odeslání upomínky. Každá platba ji dostane jen jednou, a to jen když jí ještě žádná upomínka neodešla.',
        ],
        self::TASK_QUEUE => [
            'title' => 'Fronta úloh',
            'description' => 'Odesílá e-maily zařazené k odeslání na pozadí, například hromadně rozeslané platební údaje, upomínky a potvrzení o úhradě.',
        ],
    ];

    public function __construct(private IScheduler $scheduler)
    {
    }

    /** @return array<string, ExpressionJob> */
    public function getJobs(): array
    {
        $jobs = [];

        foreach ($this->scheduler->getAll() as $name => $job) {
            if (! $job instanceof ExpressionJob) {
                throw new LogicException(sprintf('Cron job "%s" needs a cron expression in scheduler.neon.', $name));
            }

            $jobs[(string) $name] = $job;
        }

        return $jobs;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->getJobs());
    }

    public function getTitle(string $name): string
    {
        return self::DESCRIPTIONS[$name]['title'] ?? $name;
    }

    public function getDescription(string $name): string
    {
        return self::DESCRIPTIONS[$name]['description'] ?? '';
    }

    public function getDefaultSchedule(string $name): string
    {
        return ($this->getJobs()[$name] ?? null)?->getExpression()->getExpression()
            ?? throw new LogicException(sprintf('Unknown cron job "%s".', $name));
    }

    /** The administration may override the default schedule from scheduler.neon. */
    public function getSchedule(string $name, ?CronJobState $state): CronExpression
    {
        return new CronExpression($state?->getSchedule() ?? $this->getDefaultSchedule($name));
    }
}
