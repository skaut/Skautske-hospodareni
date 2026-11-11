<?php

declare(strict_types=1);

namespace Hskauting\Tests;

use IntegrationTest;
use Skaut\Skautis\Config;
use Skaut\Skautis\SessionAdapter\FakeAdapter;
use Skaut\Skautis\Skautis;
use Skaut\Skautis\User;
use Skaut\Skautis\Wsdl\WsdlManager;

abstract class SkautisTest extends IntegrationTest
{
    protected function createSkautis(string $loginId): Skautis
    {
        $config = new Config('48104fe2-b447-47f5-9a26-051c710da74e', true, true, true);
        $wsdlManager = new WsdlManager(new VcrWebServiceFactory(), $config);
        $user = new User($wsdlManager, new FakeAdapter());

        $user->setLoginData($loginId);

        return new Skautis($wsdlManager, $user);
    }
}
