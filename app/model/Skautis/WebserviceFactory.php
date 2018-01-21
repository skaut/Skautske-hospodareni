<?php

declare(strict_types=1);

namespace Model\Skautis;

use Nette\StaticClass;
use Skautis\Skautis;
use Skautis\Wsdl\Decorator\Cache\ArrayCache;
use Skautis\Wsdl\Decorator\Cache\CacheDecorator;
use Skautis\Wsdl\WebService;

final class WebserviceFactory
{

    use StaticClass;

    public static function createCached(string $name, Skautis $skautis): CacheDecorator
    {
        return new CacheDecorator($skautis->getWebService($name), new ArrayCache());
    }

    public static function create(string $name, Skautis $skautis): WebService
    {
        return $skautis->getWebService($name);
    }

}
