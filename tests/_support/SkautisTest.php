<?php

declare(strict_types=1);

namespace Hskauting\Tests;

use Codeception\Test\Unit;
use Skautis\Config;
use Skautis\SessionAdapter\FakeAdapter;
use Skautis\Skautis;
use Skautis\User;
use Skautis\Wsdl\WebServiceFactory;
use Skautis\Wsdl\WsdlManager;

abstract  class SkautisTest extends Unit
{
    protected function createSkautis(string $loginId) : Skautis
    {
        $config      = new Config('48104fe2-b447-47f5-9a26-051c710da74e', true, true, true);
        $wsdlManager = new WsdlManager(new WebServiceFactory(WebServiceWithInterception::class), $config);
        $user        = new User($wsdlManager, new FakeAdapter());

        $user->setLoginData($loginId);

        return new Skautis($wsdlManager, $user);
    }
}
