<?php

declare(strict_types=1);

namespace Model\Payment;

use Assert\Assertion;
use Nette\Mail\Mailer;
use Nette\Mail\Message;

class NullMailer implements Mailer
{
    public function send(Message $mail): void
    {
        Assertion::notNull($mail->getHeader('To'), 'There must be at least one e-mail recipient');
    }
}
