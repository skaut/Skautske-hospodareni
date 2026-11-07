<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use Codeception\Test\Unit;
use Skautis\Wsdl\WsdlException;

final class SkautisErrorsTest extends Unit
{
    /** @return array<string, array{string, bool}> */
    public function getMessages(): array
    {
        return [
            'nedostupný server' => ['Could not connect to host', true],
            'vypršelo spojení' => ['Error Fetching http headers', true],
            'neznámá adresa' => ['php_network_getaddresses: getaddrinfo for test-is.skaut.cz failed', true],
            'služba mimo provoz' => ['Service Unavailable', true],
            // As skautIS refused a role that expired, reported in Sentry.
            'odmítnutá role' => [
                'System.Web.Services.Protocols.SoapException: Server was unable to process request. ---> '
                .'System.Data.SqlClient.SqlException: Uživatel nemá na zadanou roli oprávnění',
                false,
            ],
            'odmítnutí bez názvu výjimky' => ['Server was unable to process request. ---> Uživatel nemá na zadanou roli oprávnění', false],
        ];
    }

    /** @dataProvider getMessages */
    public function testNoAnswerIsToldApartFromAnswerWithError(string $message, bool $isNoAnswer): void
    {
        self::assertSame($isNoAnswer, SkautisErrors::isNoAnswer(new WsdlException($message)));
    }
}
