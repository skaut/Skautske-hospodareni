<?php

declare(strict_types=1);

namespace App\Model\TaskQueue;

use App\Model\TaskQueue\Exception\TaskFailed;
use App\Model\TaskQueue\Exception\TaskRetryLater;
use App\Model\TaskQueue\Exception\TaskSkipped;
use RuntimeException;

/** Test handler: the payload says how the task ends; it records what ran and what was released. */
final class RecordingTaskHandler implements TaskHandler
{
    public const TYPE = 'test';

    /** @var list<string> */
    public array $handled = [];

    /** @var list<string> */
    public array $released = [];

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return 'Testovací úlohy';
    }

    public function handle(TaskContext $context): void
    {
        $payload = $context->getPayload();
        $this->handled[] = (string) $payload['name'];

        switch ($payload['behaviour'] ?? 'done') {
            case 'skip':
                throw new TaskSkipped('Není potřeba.');
            case 'fail':
                throw new TaskFailed('Nejde to.');
            case 'abort':
                throw new TaskFailed('Nejde nic.', abortJob: true);
            case 'retry':
                throw new TaskRetryLater('Později.', 60);
            case 'retryBatch':
                throw new TaskRetryLater('Limit odesílatele.', 3600, wholeBatch: true, maxAttempts: 26);
            case 'crash':
                throw new RuntimeException('Crash before the irreversible part.');
            case 'crashAfterIrreversible':
                $context->beginIrreversible();

                throw new RuntimeException('Crash after the irreversible part.');
            default:
                $context->beginIrreversible();
                $context->complete();
        }
    }

    public function release(array $payload): void
    {
        $this->released[] = (string) $payload['name'];
    }
}
