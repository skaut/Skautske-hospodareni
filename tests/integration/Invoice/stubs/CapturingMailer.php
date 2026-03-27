<?php

declare(strict_types=1);

namespace Tests\Integration\Invoice;

use Nette\Mail\Mailer;
use Nette\Mail\Message;

final class CapturingMailer implements Mailer
{
    private ?Message $lastMessage = null;

    private int $sendCount = 0;

    public function send(Message $mail): void
    {
        $this->lastMessage = $mail;
        ++$this->sendCount;
    }

    public function getLastMessage(): ?Message
    {
        return $this->lastMessage;
    }

    public function getSendCount(): int
    {
        return $this->sendCount;
    }
}
