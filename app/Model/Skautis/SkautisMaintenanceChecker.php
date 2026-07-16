<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use ErrorException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Skautis\Skautis;
use Throwable;

class SkautisMaintenanceChecker
{
    private const CACHE_KEY = 'skautis_maintenance';

    /** Cache TTL in seconds */
    private const CACHE_TTL = 30;

    /** Connection timeout for the health check in seconds */
    private const CHECK_TIMEOUT = 3;

    private Cache $cache;

    public function __construct(
        private Skautis $skautis,
        Storage $storage,
    ) {
        $this->cache = new Cache($storage, 'skautis');
    }

    public function isMaintenance(): bool
    {
        /** @var bool|null $cached */
        $cached = $this->cache->load(self::CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $previousTimeout = (int) ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', (string) self::CHECK_TIMEOUT);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $result = $this->skautis->isMaintenance();
        } catch (Throwable) {
            $result = false;
        } finally {
            restore_error_handler();
            ini_set('default_socket_timeout', (string) $previousTimeout);
        }

        $this->cache->save(self::CACHE_KEY, $result, [
            Cache::Expire => self::CACHE_TTL.' seconds',
        ]);

        return $result;
    }
}
