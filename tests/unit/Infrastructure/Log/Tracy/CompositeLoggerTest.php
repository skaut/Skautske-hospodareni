<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Log\Tracy;

use Codeception\Test\Unit;
use Mockery as m;
use RuntimeException;
use Tracy\ILogger;

final class CompositeLoggerTest extends Unit
{
    public function testLogsMessageToEveryLogger(): void
    {
        $firstLogger = m::mock(ILogger::class);
        $secondLogger = m::mock(ILogger::class);
        $firstLogger->expects('log')->with('Test message', ILogger::WARNING);
        $secondLogger->expects('log')->with('Test message', ILogger::WARNING);

        $logger = new CompositeLogger($firstLogger, $secondLogger);

        $logger->log('Test message', ILogger::WARNING);
    }

    public function testLogsToOtherLoggersWhenOneFails(): void
    {
        $exception = new RuntimeException('Unable to write a log file.');
        $firstLogger = m::mock(ILogger::class);
        $secondLogger = m::mock(ILogger::class);
        $firstLogger->expects('log')->with('Test message', ILogger::ERROR)->andThrow($exception);
        $secondLogger->expects('log')->with('Test message', ILogger::ERROR);

        $logger = new CompositeLogger($firstLogger, $secondLogger);

        $this->expectExceptionObject($exception);

        $logger->log('Test message', ILogger::ERROR);
    }
}
