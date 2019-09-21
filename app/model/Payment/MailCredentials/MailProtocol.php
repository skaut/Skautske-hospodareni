<?php

declare(strict_types=1);

namespace Model\Payment\MailCredentials;

use Consistence\Enum\Enum;

final class MailProtocol extends Enum
{
    public const SSL   = 'ssl';
    public const TLS   = 'tls';
    public const PLAIN = '';

    public static function SSL() : self
    {
        return self::get(self::SSL);
    }

    public static function TLS() : self
    {
        return self::get(self::TLS);
    }

    public static function PLAIN() : self
    {
        return self::get(self::PLAIN);
    }
}
