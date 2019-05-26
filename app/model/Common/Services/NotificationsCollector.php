<?php

declare(strict_types=1);

namespace Model\Common\Services;

/**
 * This class is intended as bridge between certain model notifications
 * and UI (flash messages).
 */
class NotificationsCollector
{
    public const ERROR = 'error';

    /**
     * notification type => [message => number of notifications with this message]
     *
     * @var array<string, array<string, int>>
     */
    private $notifications = [
        self::ERROR => [],
    ];

    public function error(string $message) : void
    {
        $this->notifications[self::ERROR][$message] = ($this->notifications[self::ERROR][$message] ?? 0) + 1;
    }

    /**
     * Returns collected notifications and clears notifications queue
     *
     * @return array<(string|int)[]> Array of (type, message, count) tuples i.e. [["info", "Something happened", 2]]
     */
    public function popNotifications() : array
    {
        $result = [];

        foreach ($this->notifications as $type => $notifications) {
            $this->notifications[$type] = [];

            foreach ($notifications as $message => $count) {
                $result[] = [$type, $message, $count];
            }
        }

        return $result;
    }
}
