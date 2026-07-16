<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Infrastructure\Skautis\LazyWebService;
use App\Model\Infrastructure\Skautis\RetryingWebService;
use Nette\StaticClass;
use Skautis\Skautis;
use Skautis\Wsdl\Decorator\Cache\ArrayCache;
use Skautis\Wsdl\Decorator\Cache\CacheDecorator;
use Skautis\Wsdl\WebServiceInterface;

/** @see LazyWebService */
final class WebserviceFactory
{
    use StaticClass;

    public static function createCached(string $name, Skautis $skautis): CacheDecorator
    {
        return new CacheDecorator(self::create($name, $skautis), new ArrayCache());
    }

    public static function create(string $name, Skautis $skautis): WebServiceInterface
    {
        return new RetryingWebService(new LazyWebService($name, $skautis));
    }
}
