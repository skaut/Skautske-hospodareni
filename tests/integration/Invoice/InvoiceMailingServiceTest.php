<?php

declare(strict_types=1);

namespace App\Model\Invoice\IntegrationTests;

use App\Model\Common\EmailAddress;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\User;
use App\Model\Event\Repositories\IEventRepository;
use App\Model\Export\ExportService;
use App\Model\Google\OAuthId;
use App\Model\Invoice\EmailTemplate;
use App\Model\Invoice\EmailType;
use App\Model\Invoice\Embeddable\InvoiceCustomer;
use App\Model\Invoice\Embeddable\InvoiceSupplier;
use App\Model\Invoice\Entity\Invoice;
use App\Model\Invoice\Entity\InvoiceItem;
use App\Model\Invoice\Entity\InvoiceSequence;
use App\Model\Invoice\InvoiceImageStorage;
use App\Model\Invoice\InvoiceMailingService;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Invoice\Repository\InvoiceUnitSettingRepository;
use App\Model\Mail\Repositories\IGoogleRepository;
use App\Model\Payment\MailerFactoryStub;
use App\Model\Payment\UserRepositoryStub;
use App\Model\Payment\VariableSymbol;
use App\Model\Services\PdfRenderer;
use App\Model\Services\TemplateFactory;
use App\Model\Unit\UnitService;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use IntegrationTest;
use Mockery;
use Nette\Mail\Message;
use Tests\Integration\Invoice\CapturingMailer;

final class InvoiceMailingServiceTest extends IntegrationTest
{
    private const OAUTH_ID = '42288e92-27fb-453c-9904-36a7ebd14fe2';
    private const SENDER_EMAIL = 'test@hospodareni.loc';

    private InvoiceMailingService $service;

    private InvoiceRepository $invoices;

    private UserRepositoryStub $users;

    private CapturingMailer $mailer;

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [
            Invoice::class,
            InvoiceSequence::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['Invoice/InvoiceMailingServiceTest.neon']);

        parent::_before();

        $templateFactory = $this->tester->grabService(TemplateFactory::class);
        $pdfRenderer = $this->tester->grabService(PdfRenderer::class);
        $mailerFactory = $this->tester->grabService(MailerFactoryStub::class);
        $googleRepository = $this->tester->grabService(IGoogleRepository::class);
        $this->users = $this->tester->grabService(UserRepositoryStub::class);
        $this->mailer = new CapturingMailer();
        $mailerFactory->setMailer($this->mailer);

        $this->users->setUser(new User(10, 'František Maša', self::SENDER_EMAIL));
        $this->invoices = new InvoiceRepository($this->entityManager);
        $this->service = new InvoiceMailingService(
            $this->invoices,
            $this->entityManager,
            $mailerFactory,
            $googleRepository,
            $templateFactory,
            $this->createExportService($templateFactory),
            $pdfRenderer,
            $this->users,
        );
    }

    public function testPdfIsGeneratedForInvoice(): void
    {
        $invoice = $this->createAndPersistInvoice();
        $templateFactory = $this->tester->grabService(TemplateFactory::class);
        $pdfRenderer = $this->tester->grabService(PdfRenderer::class);
        $exportService = $this->createExportService($templateFactory);

        $pdf = $pdfRenderer->renderToString($exportService->getInvoice($invoice));

        self::assertStringStartsWith('%PDF-', $pdf);
    }

    public function testInvoiceInfoEmailContainsAttachmentAndMarksInvoiceAsSent(): void
    {
        $invoice = $this->createAndPersistInvoice();

        $this->service->sendEmail($invoice->getId(), EmailType::get(EmailType::INVOICE_INFO));

        $message = $this->getLastMessage();
        self::assertSame(1, $this->mailer->getSendCount());
        self::assertSame(['first@example.test' => null, 'second@example.test' => null], $message->getHeader('To'));
        self::assertSame([self::SENDER_EMAIL => null], $message->getFrom());
        self::assertSame('Faktura 2026-001 pro Odběratel', $message->getSubject());
        self::assertStringContainsString('VS 123456', $message->getHtmlBody());

        $attachments = $message->getAttachments();
        self::assertCount(1, $attachments);
        self::assertSame('attachment; filename="2026-001.pdf"', $attachments[0]->getHeader('Content-Disposition'));
        self::assertSame('application/pdf', $attachments[0]->getHeader('Content-Type'));
        self::assertStringStartsWith('%PDF-', $attachments[0]->getBody());

        /** @var Invoice $reloaded */
        $reloaded = $this->invoices->find($invoice->getId());
        self::assertTrue($reloaded->hasBeenSent());
        self::assertCount(1, $reloaded->getSentEmails());
        self::assertTrue($reloaded->getSentEmails()[0]->wasSuccessful());
        self::assertTrue($reloaded->getSentEmails()[0]->getType()->equalsValue(EmailType::INVOICE_INFO));
    }

    public function testReminderEmailUsesReminderTemplateForOverdueInvoice(): void
    {
        $invoice = $this->createAndPersistInvoice(new DateTimeImmutable('2026-03-01'));
        $invoice->recordEmailAttempt(
            EmailType::get(EmailType::INVOICE_INFO),
            new DateTimeImmutable('2026-02-15 09:00:00'),
            'František Maša',
        );
        $this->entityManager->flush();

        $this->service->sendEmail($invoice->getId(), EmailType::get(EmailType::INVOICE_REMINDER), true);

        $message = $this->getLastMessage();
        self::assertSame('Upomínka 2026-001', $message->getSubject());
        self::assertStringContainsString('po splatnosti od 1.3.2026', $message->getHtmlBody());

        /** @var Invoice $reloaded */
        $reloaded = $this->invoices->find($invoice->getId());
        self::assertCount(2, $reloaded->getSentEmails());
        self::assertTrue($reloaded->getSentEmails()[1]->getType()->equalsValue(EmailType::INVOICE_REMINDER));
        self::assertTrue($reloaded->getSentEmails()[1]->wasSuccessful());
    }

    private function createAndPersistInvoice(?DateTimeImmutable $dueDate = null): Invoice
    {
        $sequence = new InvoiceSequence(
            123,
            'INV',
            2026,
            'Hlavní řada',
            null,
            OAuthId::fromString(self::OAUTH_ID),
            14,
        );
        $sequence->setSequenceId(1);
        $sequence->updateEmail(
            EmailType::get(EmailType::INVOICE_INFO),
            new EmailTemplate(
                'Faktura %number% pro %customer_name%',
                'Dobrý den, VS %vs%, částka %amount%, vystavil %user%.',
            ),
        );
        $sequence->updateEmail(
            EmailType::get(EmailType::INVOICE_REMINDER),
            new EmailTemplate(
                'Upomínka %number%',
                'Faktura je po splatnosti od %maturity%.',
            ),
        );

        $invoice = new Invoice(
            $sequence,
            new InvoiceSupplier(123, 'Středisko Test', 'Křižíkova 12', 'Praha', '18600', '12345678', '+420123456789'),
            new InvoiceCustomer('Odběratel', 'Ulice', 'Brno', '60200', '1', '2', '87654321'),
            'Tester',
            $dueDate ?? new DateTimeImmutable('2026-03-20'),
            new DateTimeImmutable('2026-02-10'),
            new DateTimeImmutable('2026-02-10'),
            \App\Model\Invoice\Enum\InvoicePaymentType::TRANSFER,
            AccountNumber::fromString('2300228890/2010'),
            '2026-001',
            new VariableSymbol('123456'),
        );
        $invoice->addItem(new InvoiceItem(BigDecimal::of('121.00'), 'Položka', 1, 'ks'));
        $invoice->updateEmailRecipients([
            new EmailAddress('first@example.test'),
            new EmailAddress('second@example.test'),
        ]);

        $this->entityManager->persist($sequence);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $invoice;
    }

    private function createExportService(TemplateFactory $templateFactory): ExportService
    {
        $invoiceUnitSettings = Mockery::mock(InvoiceUnitSettingRepository::class);
        $invoiceUnitSettings->shouldReceive('findByUnitAndYear')->andReturn(null);

        return new ExportService(
            Mockery::mock(UnitService::class),
            $templateFactory,
            Mockery::mock(IEventRepository::class),
            Mockery::mock(QueryBus::class),
            $invoiceUnitSettings,
            $this->tester->grabService(InvoiceImageStorage::class),
            $this->tester->grabService(IGoogleRepository::class),
        );
    }

    private function getLastMessage(): Message
    {
        $message = $this->mailer->getLastMessage();
        self::assertNotNull($message);

        return $message;
    }
}
