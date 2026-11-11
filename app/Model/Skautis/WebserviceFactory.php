<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Infrastructure\Skautis\LazyWebService;
use App\Model\Infrastructure\Skautis\RetryingWebService;
use Nette\Bridges\Psr\PsrCacheAdapter;
use Nette\Caching\Storages\MemoryStorage;
use Nette\StaticClass;
use Skaut\Skautis\Skautis;
use Skaut\Skautis\Wsdl\Decorator\Cache\CacheDecorator;
use Skaut\Skautis\Wsdl\WebServiceInterface;

/** @see LazyWebService */
final class WebserviceFactory
{
    use StaticClass;

    /** The cache lives in memory for one request, so the lifetime only has to outlast it. */
    private const CACHE_TTL_SECONDS = 3600;

    public static function createCached(string $name, Skautis $skautis): CacheDecorator
    {
        return new CacheDecorator(self::create($name, $skautis), new PsrCacheAdapter(new MemoryStorage()), self::CACHE_TTL_SECONDS);
    }

    public static function create(string $name, Skautis $skautis): WebServiceInterface
    {
        return new RetryingWebService(new LazyWebService($name, $skautis));
    }
}
