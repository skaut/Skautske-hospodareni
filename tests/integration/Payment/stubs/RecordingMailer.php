<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Nette\Mail\Mailer;
use Nette\Mail\Message;

/** Keeps the built e-mails instead of sending them. */
class RecordingMailer implements Mailer
{
    /** @var list<Message> */
    public array $messages = [];

    public function send(Message $mail): void
    {
        $this->messages[] = $mail;
    }
}
