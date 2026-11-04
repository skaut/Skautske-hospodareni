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

    /** Keep in sync with the jobs in scheduler.neon. */
    private const DESCRIPTIONS = [
        self::PAYMENT_PAIRING => [
            'title' => 'Párování plateb',
            'description' => 'Páruje transakce z účtů napojených na FIO s platbami ve skupinách, kde je zapnuté automatické párování.',
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
