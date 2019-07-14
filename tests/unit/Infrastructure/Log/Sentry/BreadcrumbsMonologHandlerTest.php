<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log\Sentry;

use Codeception\Test\Unit;
use Mockery;
use Monolog\Logger;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

final class BreadcrumbsMonologHandlerTest extends Unit
{
    /** @var Scope */
    private $scope;

    /** @var BreadcrumbsMonologHandler */
    private $handler;

    public function _before() : void
    {
        $this->scope = new Scope();

        $hub = Mockery::mock(HubInterface::class);

        $hub->shouldReceive('configureScope')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                return $callback($this->scope);
            });

        $this->handler = new BreadcrumbsMonologHandler($hub);
    }

    public function testWriteRecord() : void
    {
        $record = [
            'message' => 'Test message',
            'level' => Logger::WARNING,
            'context' => ['option' => 'value'],
            'extra' => [],
        ];

        $this->handler->handle($record);

        $breadcrumbs = $this->scope->getBreadcrumbs();

        $this->assertCount(1, $breadcrumbs);
        $this->assertSame($record['message'], $breadcrumbs[0]->getMessage());
        $this->assertSame($record['context'], $breadcrumbs[0]->getMetadata());
        $this->assertSame('default', $breadcrumbs[0]->getType());
    }

    protected function _after() : void
    {
        Mockery::close();
    }
}
