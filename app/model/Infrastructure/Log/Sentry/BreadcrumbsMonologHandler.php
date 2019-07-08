<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log\Sentry;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Sentry\Breadcrumb;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

final class BreadcrumbsMonologHandler extends AbstractProcessingHandler
{
    private const LEVELS = [
        Logger::DEBUG => Breadcrumb::LEVEL_DEBUG,
        Logger::INFO => Breadcrumb::LEVEL_INFO,
        Logger::NOTICE => Breadcrumb::LEVEL_INFO,
        Logger::WARNING => Breadcrumb::LEVEL_WARNING,
        Logger::ERROR => Breadcrumb::LEVEL_ERROR,
        Logger::CRITICAL => Breadcrumb::LEVEL_CRITICAL,
        Logger::ALERT => Breadcrumb::LEVEL_CRITICAL,
        Logger::EMERGENCY => Breadcrumb::LEVEL_CRITICAL,
    ];

    /** @var HubInterface */
    private $hub;

    public function __construct(HubInterface $hub)
    {
        parent::__construct();
        $this->hub = $hub;
    }

    /**
     * @param array<string, mixed> $record
     */
    protected function write(array $record) : void
    {
        $this->hub->configureScope(function (Scope $scope) use ($record) : void {
            $scope->addBreadcrumb(
                new Breadcrumb(
                    $this->convertMonologLevelToSentryLevel($record['level']),
                    Breadcrumb::TYPE_DEFAULT,
                    'default',
                    $record['message'],
                    $record['context'] ?? null
                )
            );
        });
    }

    /**
     * Translates the Monolog level into the Sentry breadcrumbs level.
     *
     * @param int $level The Monolog log level
     */
    private function convertMonologLevelToSentryLevel(int $level) : string
    {
        return self::LEVELS[$level] ?? Breadcrumb::LEVEL_INFO;
    }
}
