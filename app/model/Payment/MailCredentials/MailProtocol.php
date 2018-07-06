<?php

declare(strict_types=1);

namespace Model\Payment\MailCredentials;

use Consistence\Enum\Enum;

final class MailProtocol extends Enum
{
    public const SSL   = 'ssl';
    public const TLS   = 'tls';
    public const PLAIN = '';
}
