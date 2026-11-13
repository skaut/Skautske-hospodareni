<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use Skautis\Wsdl\WsdlException;

use function str_starts_with;

/**
 * Tells a skautIS that did not answer at all apart from one that answered with an error. Its errors start with
 * "Server was unable to process request." also when it only refuses something, e.g. a role the user no longer has.
 *
 * The only place that knows the messages of a skautIS outage; the retries, the error page and the acceptance tests use it.
 */
final class SkautisErrors
{
    /** skautIS did not get the request or refused it before processing it, so any request can be sent again. */
    public const NOT_PROCESSED_MESSAGES = [
        'Could not connect to host',
        'php_network_getaddresses',
        'getaddrinfo',
        'Name or service not known',
        'Temporary failure in name resolution',
        'Connection timed out',
        'Failed to connect',
        'SSL: Handshake timed out',
        'Service Unavailable',
    ];

    /** skautIS did not answer, but it may have processed the request. */
    public const RESPONSE_LOST_MESSAGES = [
        'Error Fetching http headers',
    ];

    private const SERVER_ERROR_MESSAGE = 'Server was unable to process request.';

    public static function isNoAnswer(WsdlException $exception): bool
    {
        return self::isNotProcessed($exception) || self::startsWithAny($exception, self::RESPONSE_LOST_MESSAGES);
    }

    public static function isNotProcessed(WsdlException $exception): bool
    {
        return self::startsWithAny($exception, self::NOT_PROCESSED_MESSAGES);
    }

    /** skautIS answered, but failed or refused the request. */
    public static function isServerError(WsdlException $exception): bool
    {
        return str_starts_with($exception->getMessage(), self::SERVER_ERROR_MESSAGE);
    }

    /** @param list<string> $messages */
    private static function startsWithAny(WsdlException $exception, array $messages): bool
    {
        foreach ($messages as $message) {
            if (str_starts_with($exception->getMessage(), $message)) {
                return true;
            }
        }

        return false;
    }
}
