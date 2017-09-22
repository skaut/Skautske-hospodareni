<?php

namespace Model\Skautis;


use Nette\StaticClass;
use Skautis\Skautis;
use Skautis\Wsdl\Decorator\Cache\ArrayCache;
use Skautis\Wsdl\Decorator\Cache\CacheDecorator;
use Skautis\Wsdl\WebServiceFactoryInterface;
use Skautis\Wsdl\WebServiceInterface;

final class WebserviceFactory
{

    use StaticClass;

    public static function createOrganizationUnit(Skautis $skautis): CacheDecorator
    {
        return self::createWebservice($skautis, 'org');
    }

    public static function createEvent(Skautis $skautis): WebServiceInterface
    {
        return $skautis->getWebService('event');
    }

    private static function createWebservice(Skautis $skautis, string $name): CacheDecorator
    {
        return new CacheDecorator($skautis->getWebService($name), new ArrayCache());
    }

}
