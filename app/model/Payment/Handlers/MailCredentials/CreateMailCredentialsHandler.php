<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\MailCredentials;

use DateTimeImmutable;
use Model\Common\Repositories\IUserRepository;
use Model\Mail\IMailerFactory;
use Model\Payment\Commands\CreateMailCredentials;
use Model\Payment\EmailNotSet;
use Model\Payment\MailCredentials;
use Model\Payment\Repositories\IMailCredentialsRepository;
use Model\Services\TemplateFactory;
use Nette\Mail\Message;

class CreateMailCredentialsHandler
{
    private IMailCredentialsRepository $credentials;

    private TemplateFactory $templateFactory;

    private IMailerFactory $mailerFactory;

    private IUserRepository $users;

    public function __construct(IMailCredentialsRepository $credentials, TemplateFactory $templateFactory, IMailerFactory $mailerFactory, IUserRepository $users)
    {
        $this->credentials     = $credentials;
        $this->templateFactory = $templateFactory;
        $this->mailerFactory   = $mailerFactory;
        $this->users           = $users;
    }

    public function __invoke(CreateMailCredentials $command) : void
    {
        $credentials = new MailCredentials(
            $command->getUnitId(),
            $command->getHost(),
            $command->getUsername(),
            $command->getPassword(),
            $command->getProtocol(),
            $command->getSender(),
            new DateTimeImmutable()
        );

        $this->trySendViaSmtp($credentials, $command->getUserId());

        $this->credentials->save($credentials);
    }

    /**
     * Send test email to user who tries to add SMTP
     *
     * @throws EmailNotSet
     */
    private function trySendViaSmtp(MailCredentials $credentials, int $userId) : void
    {
        $mailer = $this->mailerFactory->create($credentials);

        $user = $this->users->find($userId);
        if ($user->getEmail() === null) {
            throw new EmailNotSet();
        }

        $template = $this->templateFactory->create(TemplateFactory::SMTP_CREDENTIALS_ADDED, [
            'host' => $credentials->getHost(),
            'username' => $credentials->getUsername(),
            'protocol' => $credentials->getProtocol()->getValue(),
            'sender' => $credentials->getSender(),
        ]);

        $mail = new Message();
        $mail->setSubject('Nový email v Hospodaření')
            ->setFrom($credentials->getSender(), 'Skautské Hospodaření')// email gets rewritten on SMTP
            ->addTo($user->getEmail(), $user->getName())
            ->setHtmlBody($template);

        $mailer->send($mail);
    }
}
