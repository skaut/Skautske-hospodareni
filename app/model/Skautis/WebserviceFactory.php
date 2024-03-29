<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Infrastructure\Skautis\LazyWebService;
use Nette\StaticClass;
use Skautis\Skautis;
use Skautis\Wsdl\Decorator\Cache\ArrayCache;
use Skautis\Wsdl\Decorator\Cache\CacheDecorator;

/** @see LazyWebService */
final class WebserviceFactory
{
    use StaticClass;

    public static function createCached(string $name, Skautis $skautis): CacheDecorator
    {
        return new CacheDecorator(self::create($name, $skautis), new ArrayCache());
    }

    public static function create(string $name, Skautis $skautis): LazyWebService
    {
        return new LazyWebService($name, $skautis);
    }
}
