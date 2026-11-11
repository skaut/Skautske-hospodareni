<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Skautis;

use App\Model\Skautis\SkautisErrors;
use LogicException;
use Skaut\Skautis\Wsdl\AuthenticationException;
use Skaut\Skautis\Wsdl\PermissionException;
use Skaut\Skautis\Wsdl\WebServiceInterface;
use Skaut\Skautis\Wsdl\WsdlException;

use function stripos;
use function usleep;

final class RetryingWebService implements WebServiceInterface
{
    private const MAX_ATTEMPTS = 3;
    private const RETRY_DELAY_MICROSECONDS = 250_000;

    public function __construct(private WebServiceInterface $inner)
    {
    }

    /** @param array<int|string, mixed> $arguments */
    public function call(string $functionName, array $arguments = []): mixed
    {
        return $this->runWithRetry($functionName, fn (): mixed => $this->inner->call($functionName, $arguments));
    }

    /** @param array<int|string, mixed> $arguments */
    public function __call(string $functionName, array $arguments): mixed
    {
        return $this->runWithRetry($functionName, fn (): mixed => $this->inner->__call($functionName, $arguments));
    }

    /** @param callable(): mixed $callback */
    private function runWithRetry(string $functionName, callable $callback): mixed
    {
        for ($attempt = 1;; ++$attempt) {
            try {
                return $callback();
            } catch (AuthenticationException|PermissionException $exception) {
                throw $exception;
            } catch (WsdlException $exception) {
                if ($attempt >= self::MAX_ATTEMPTS || ! $this->isRetryable($functionName, $exception)) {
                    throw $exception;
                }

                usleep(self::RETRY_DELAY_MICROSECONDS * $attempt);
            }
        }

        throw new LogicException('Retry loop ended unexpectedly.');
    }

    /**
     * A request that skautIS did not process can always be sent again. When only its answer got lost, skautIS
     * may have processed it: reads and updates can be repeated, a repeated insert could create a duplicate.
     */
    private function isRetryable(string $functionName, WsdlException $exception): bool
    {
        if (SkautisErrors::isNotProcessed($exception)) {
            return true;
        }

        return SkautisErrors::isNoAnswer($exception) && stripos($functionName, 'Insert') === false;
    }
}
