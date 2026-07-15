<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use App\Model\Mail\SystemMailer;
use App\Model\Services\TemplateFactory;
use App\Model\User\Entity\InvoiceAccessRequest;
use App\Model\User\InvoiceAccessNotificationService;
use Codeception\Test\Unit;
use Latte\Engine;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Mail\Mailer;
use Nette\Mail\Message;

final class InvoiceAccessNotificationServiceTest extends Unit
{
    public function testRequestReceivedNotificationIsSentToRequester(): void
    {
        $mailer = $this->createMailer();
        $service = new InvoiceAccessNotificationService($this->createSystemMailer($mailer));

        $service->notifyRequestReceived($this->createRequest('jana.kvapilova@example.test'));

        self::assertInstanceOf(Message::class, $mailer->message);
        self::assertSame(['noreply@h.skauting.cz' => 'Skautské hospodaření'], $mailer->message->getHeader('From'));
        self::assertSame(['jana.kvapilova@example.test' => 'Jana Kvapilová'], $mailer->message->getHeader('To'));
        self::assertSame('Žádost o přístup k fakturaci byla přijata', (string) $mailer->message->getSubject());
        self::assertStringContainsString('Přístup vám udělíme, jakmile to bude technicky možné.', (string) $mailer->message->getBody());
        self::assertStringContainsString('Po vytvoření si prosím faktury pro jistotu ukládejte také mimo systém.', (string) $mailer->message->getBody());
        self::assertStringContainsString('se ještě mohou měnit na základě uživatelských podnětů.', (string) $mailer->message->getBody());
    }

    public function testAccessApprovedNotificationIsSentToRequester(): void
    {
        $mailer = $this->createMailer();
        $service = new InvoiceAccessNotificationService($this->createSystemMailer($mailer));

        $service->notifyAccessApproved($this->createRequest('jana.kvapilova@example.test'));

        self::assertInstanceOf(Message::class, $mailer->message);
        self::assertSame(['jana.kvapilova@example.test' => 'Jana Kvapilová'], $mailer->message->getHeader('To'));
        self::assertSame('Fakturace ve Skautském hospodaření je zpřístupněna', (string) $mailer->message->getSubject());
        self::assertStringContainsString('váš předběžný přístup k fakturaci ve Skautském hospodaření byl schválen.', (string) $mailer->message->getBody());
        self::assertStringContainsString('Platby -> Faktury', (string) $mailer->message->getBody());
    }

    public function testNoNotificationIsSentWithoutRequesterEmail(): void
    {
        $mailer = $this->createMailer();
        $service = new InvoiceAccessNotificationService($this->createSystemMailer($mailer));

        self::assertFalse($service->notifyRequestReceived($this->createRequest(null)));
        self::assertFalse($service->notifyAccessApproved($this->createRequest(null)));

        self::assertNull($mailer->message);
    }

    private function createRequest(?string $requesterEmail): InvoiceAccessRequest
    {
        return new InvoiceAccessRequest(
            1882,
            23378,
            117123,
            'Jana Kvapilová',
            $requesterEmail,
            'Chci testovat fakturaci.',
        );
    }

    private function createMailer(): CapturingMailer
    {
        return new CapturingMailer();
    }

    private function createSystemMailer(Mailer $mailer): SystemMailer
    {
        return new SystemMailer(
            $mailer,
            false,
            false,
            'https://h.skauting.cz',
            new TemplateFactory(new class implements LatteFactory {
                public function create(): Engine
                {
                    return new Engine();
                }
            }),
        );
    }
}

final class CapturingMailer implements Mailer
{
    public ?Message $message = null;

    public function send(Message $mail): void
    {
        $this->message = $mail;
    }
}
