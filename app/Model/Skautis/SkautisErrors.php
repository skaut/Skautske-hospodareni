<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use Skautis\Wsdl\WsdlException;

use function str_starts_with;

/**
 * Tells a skautIS that did not answer at all apart from one that answered with an error. Its errors start with
 * "Server was unable to process request." also when it only refuses something, e.g. a role the user no longer has.
 */
final class SkautisErrors
{
    private const NO_ANSWER_MESSAGES = [
        'Could not connect to host',
        'Error Fetching http headers',
        'php_network_getaddresses',
        'Service Unavailable',
    ];

    public static function isNoAnswer(WsdlException $exception): bool
    {
        foreach (self::NO_ANSWER_MESSAGES as $message) {
            if (str_starts_with($exception->getMessage(), $message)) {
                return true;
            }
        }

        return false;
    }
}
