<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Skautis;

use LogicException;
use Skautis\Wsdl\AuthenticationException;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WebServiceInterface;
use Skautis\Wsdl\WsdlException;

use function str_contains;
use function usleep;

final class RetryingWebService implements WebServiceInterface
{
    private const MAX_ATTEMPTS = 3;
    private const RETRY_DELAY_MICROSECONDS = 250_000;

    private const RETRYABLE_MESSAGES = [
        'Could not connect to host',
        'php_network_getaddresses',
        'getaddrinfo',
        'Name or service not known',
        'Temporary failure in name resolution',
        'Connection timed out',
        'Failed to connect',
        'HTTP Error Fetching http headers',
        'SSL: Handshake timed out',
    ];

    public function __construct(private WebServiceInterface $inner)
    {
    }

    /**
     * @param string  $functionName
     * @param mixed[] $arguments
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function call($functionName, array $arguments = []): mixed
    {
        return $this->runWithRetry(fn (): mixed => $this->inner->call($functionName, $arguments));
    }

    /**
     * @param string  $functionName
     * @param mixed[] $arguments
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function __call($functionName, $arguments): mixed
    {
        return $this->runWithRetry(fn (): mixed => $this->inner->__call($functionName, $arguments));
    }

    /**
     * @param string $eventName
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function subscribe($eventName, callable $callback): void
    {
        $this->inner->subscribe($eventName, $callback);
    }

    /** @param callable(): mixed $callback */
    private function runWithRetry(callable $callback): mixed
    {
        for ($attempt = 1;; ++$attempt) {
            try {
                return $callback();
            } catch (AuthenticationException|PermissionException $exception) {
                throw $exception;
            } catch (WsdlException $exception) {
                if ($attempt >= self::MAX_ATTEMPTS || ! $this->isRetryable($exception)) {
                    throw $exception;
                }

                usleep(self::RETRY_DELAY_MICROSECONDS * $attempt);
            }
        }

        throw new LogicException('Retry loop ended unexpectedly.');
    }

    private function isRetryable(WsdlException $exception): bool
    {
        foreach (self::RETRYABLE_MESSAGES as $message) {
            if (str_contains($exception->getMessage(), $message)) {
                return true;
            }
        }

        $previous = $exception->getPrevious();

        if ($previous === null) {
            return false;
        }

        foreach (self::RETRYABLE_MESSAGES as $message) {
            if (str_contains($previous->getMessage(), $message)) {
                return true;
            }
        }

        return false;
    }
}
