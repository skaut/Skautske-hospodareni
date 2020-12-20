<?php

declare(strict_types=1);

namespace Model\Payment;

use Assert\Assertion;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

class NullMailer implements IMailer
{
    public function send(Message $mail) : void
    {
        Assertion::notNull($mail->getHeader('To'), 'There must be at least one email recipient');
    }
}
