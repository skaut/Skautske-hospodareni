<?php

namespace Model\Skautis;


use Nette\StaticClass;
use Skautis\Skautis;
use Skautis\Wsdl\Decorator\Cache\ArrayCache;
use Skautis\Wsdl\Decorator\Cache\CacheDecorator;

final class WebserviceFactory
{

    use StaticClass;

    public static function createUnits(Skautis $skautis): CacheDecorator
    {
        return self::createWebservice($skautis, 'org');
    }

    private static function createWebservice(Skautis $skautis, string $name): CacheDecorator
    {
        return new CacheDecorator($skautis->getWebService($name), new ArrayCache());
    }

}
