<?php

declare(strict_types=1);

namespace Model\Common\Services;

use Codeception\Test\Unit;

final class NotificationsCollectorTest extends Unit
{
    public function testAddErrorNotification() : void
    {
        $collector = new NotificationsCollector();

        $collector->error('test1');
        $collector->error('test2');
        $collector->error('test1');

        $this->assertSame(
            [
                ['error', 'test1', 2],
                ['error', 'test2', 1],
            ],
            $collector->popNotifications()
        );
    }

    public function testPopNotificationsClearsQueue() : void
    {
        $collector = new NotificationsCollector();
        $collector->error('test');

        $collector->popNotifications();

        $this->assertSame([], $collector->popNotifications());
    }
}
