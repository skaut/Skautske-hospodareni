<?php

declare(strict_types=1);

namespace Model\Skautis;

use Nette\Caching\IStorage;
use Nette\StaticClass;
use Skautis\Nette\CacheAdapter;
use Skautis\Skautis;
use Skautis\Wsdl\Decorator\Cache\ArrayCache;
use Skautis\Wsdl\Decorator\Cache\CacheDecorator;
use Skautis\Wsdl\WebService;

final class WebserviceFactory
{
    use StaticClass;

    public static function createCached(string $name, Skautis $skautis) : CacheDecorator
    {
        return new CacheDecorator($skautis->getWebService($name), new ArrayCache());
    }

    public static function createOrg(Skautis $skautis, IStorage $storage) : CacheDecorator
    {
        $cache = new CacheAdapter($storage, 'skautis-org');
        $cache->setExpiration('2 hours');

        return new CacheDecorator($skautis->getWebService('org'), $cache);
    }

    public static function create(string $name, Skautis $skautis) : WebService
    {
        return $skautis->getWebService($name);
    }
}
