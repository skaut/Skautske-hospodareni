<?php

declare(strict_types=1);

namespace App\Presenter;

use App\ErrorPresenter;
use App\Model\Common\Services\CommandBus;
use Codeception\Test\Unit;
use Mockery;
use Psr\Log\NullLogger;
use ReflectionMethod;
use Skaut\Skautis\Wsdl\WsdlException;

/**
 * A skautIS that did not answer or failed is shown with the skautIS unavailable message, not as a server error.
 */
final class ErrorPresenterSkautisUnavailableTest extends Unit
{
    /** @return array<string, array{string, bool}> */
    public function getMessages(): array
    {
        return [
            'ztracená odpověď' => ['Error Fetching http headers', true],
            'nedostupný server' => ['Could not connect to host', true],
            'služba mimo provoz' => ['Service Unavailable', true],
            'chyba skautISu' => ['Server was unable to process request. ---> Uživatel nemá na zadanou roli oprávnění', true],
            'jiná chyba' => ['Function ("UnitDetail") is not a valid method for this service', false],
        ];
    }

    /** @dataProvider getMessages */
    public function testSkautisOutageIsRecognised(string $message, bool $isUnavailable): void
    {
        $presenter = new ErrorPresenter(new NullLogger(), Mockery::mock(CommandBus::class));
        $method = new ReflectionMethod($presenter, 'isSkautisUnavailable');

        self::assertSame($isUnavailable, $method->invoke($presenter, new WsdlException($message)));
    }
}
