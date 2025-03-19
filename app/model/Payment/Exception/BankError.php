<?php

declare(strict_types=1);

namespace Model\Payment;

use Exception;
use GuzzleHttp\Exception\ServerException;
use SimpleXMLElement;

use function trim;

final class BankError extends Exception
{
    public static function fromServerException(ServerException $exception): self
    {
        return new self(self::extractMessage($exception), $exception->getCode(), $exception);
    }

    public static function fromMessage(string $message, int $code): self
    {
        return new self($message, $code);
    }

    private static function extractMessage(ServerException $exception): string
    {
        $body = $exception->getResponse()->getBody()->getContents();

        if (trim($body) === '') {
            return $exception->getMessage();
        }

        $result = new SimpleXMLElement($body);

        return (string) ($result->ordersDetails->detail->messages->message ?? '');
    }
}
