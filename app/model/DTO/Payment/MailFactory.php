<?php

namespace Model\DTO\Payment;

use Model\Payment\MailCredentials;

class MailFactory
{

    public static function create(MailCredentials $credentials) : Mail
    {
        return new Mail(
            $credentials->getId(),
            $credentials->getUnitId(),
            $credentials->getUsername(),
            $credentials->getHost(),
            $credentials->getProtocol()->getValue()
        );
    }

}
