<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use Codeception\Test\Unit;
use Skautis\Wsdl\WsdlException;
use SoapFault;

final class SkautisErrorsTest extends Unit
{
    /** @return array<string, array{string, bool, bool, bool}> message, no answer, not processed, server error */
    public function getMessages(): array
    {
        return [
            'nedostupný server' => ['Could not connect to host', true, true, false],
            'ztracená odpověď' => ['Error Fetching http headers', true, false, false],
            'neznámá adresa' => ['php_network_getaddresses: getaddrinfo for test-is.skaut.cz failed', true, true, false],
            'služba mimo provoz' => ['Service Unavailable', true, true, false],
            // As skautIS refused a role that expired, reported in Sentry.
            'odmítnutá role' => [
                'System.Web.Services.Protocols.SoapException: Server was unable to process request. ---> '
                .'System.Data.SqlClient.SqlException: Uživatel nemá na zadanou roli oprávnění',
                false,
                false,
                false,
            ],
            'odmítnutí bez názvu výjimky' => ['Server was unable to process request. ---> Uživatel nemá na zadanou roli oprávnění', false, false, true],
            // skautIS answered, only its own backend failed; the text of an outage inside does not make it one.
            'chyba uvnitř skautISu' => ['Server was unable to process request. ---> Could not connect to host', false, false, true],
        ];
    }

    /** @dataProvider getMessages */
    public function testNoAnswerIsToldApartFromAnswerWithError(string $message, bool $isNoAnswer, bool $isNotProcessed, bool $isServerError): void
    {
        // skautis/skautis wraps the SoapFault with the same message.
        $exception = new WsdlException($message, 0, new SoapFault('Server', $message));

        self::assertSame($isNoAnswer, SkautisErrors::isNoAnswer($exception));
        self::assertSame($isNotProcessed, SkautisErrors::isNotProcessed($exception));
        self::assertSame($isServerError, SkautisErrors::isServerError($exception));
    }
}
