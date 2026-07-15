<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\Services\TemplateFactory;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;

use function parse_url;

final class SystemMailer
{
    public function __construct(
        private Mailer $debugMailer,
        private bool $sendEmail,
        private bool $productionMode,
        private string $appBaseUrl,
        private TemplateFactory $templateFactory,
    ) {
    }

    /**
     * @param array<string, mixed>                                                  $parameters
     * @param array<string, string|null>                                            $recipients
     * @param array{email: string, name?: string|null}|null                         $replyTo
     * @param list<array{name: string, contents: string, contentType: string|null}> $attachments
     */
    public function send(
        SystemEmailTemplate $template,
        array $parameters,
        array $recipients,
        ?string $fromEmail = null,
        ?array $replyTo = null,
        array $attachments = [],
    ): Message {
        $message = (new Message())
            ->setFrom($fromEmail ?? $this->createDefaultSender(), 'Skautské hospodaření')
            ->setSubject($template->subject($parameters))
            ->setBody($this->templateFactory->create($template->templateFile(), $parameters));

        foreach ($recipients as $email => $name) {
            $message->addTo($email, $name);
        }

        if ($replyTo !== null) {
            $message->addReplyTo($replyTo['email'], $replyTo['name'] ?? null);
        }

        foreach ($attachments as $attachment) {
            $message->addAttachment(
                $attachment['name'],
                $attachment['contents'],
                $attachment['contentType'],
            );
        }

        $this->createMailer()->send($message);

        return $message;
    }

    private function createDefaultSender(): string
    {
        $host = parse_url($this->appBaseUrl, PHP_URL_HOST) ?: 'localhost';

        return 'noreply@'.$host;
    }

    private function createMailer(): Mailer
    {
        return $this->sendEmail && $this->productionMode
            ? new SendmailMailer()
            : $this->debugMailer;
    }
}
