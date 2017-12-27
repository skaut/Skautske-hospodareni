<?php

namespace Model\Payment\Handlers\MailCredentials;

use Model\Mail\IMailerFactory;
use Model\Payment\Commands\CreateMailCredentials;
use Model\Payment\EmailNotSetException;
use Model\Payment\MailCredentials;
use Model\Payment\Repositories\IMailCredentialsRepository;
use Model\Payment\Repositories\IUserRepository;
use Model\Services\TemplateFactory;
use Nette\Mail\Message;
use Nette\Mail\SmtpException;

class CreateMailCredentialsHandler
{

    /** @var IMailCredentialsRepository */
    private $credentials;

    /** @var TemplateFactory */
    private $templateFactory;

    /** @var IMailerFactory */
    private $mailerFactory;

    /** @var IUserRepository */
    private $users;

    public function __construct(IMailCredentialsRepository $credentials, TemplateFactory $templateFactory, IMailerFactory $mailerFactory, IUserRepository $users)
    {
        $this->credentials = $credentials;
        $this->templateFactory = $templateFactory;
        $this->mailerFactory = $mailerFactory;
        $this->users = $users;
    }

    public function handle(CreateMailCredentials $command): void
    {
        $credentials = new MailCredentials(
            $command->getUnitId(),
            $command->getHost(),
            $command->getUsername(),
            $command->getPassword(),
            $command->getProtocol(),
            $command->getSender(),
            new \DateTimeImmutable()
        );

        $this->trySendViaSmtp($credentials, $command->getUserId());

        $this->credentials->save($credentials);
    }

    /**
     * Send test email to user who tries to add SMTP
     * @throws EmailNotSetException
     * @throws SmtpException
     */
    private function trySendViaSmtp(MailCredentials $credentials, int $userId): void
    {
        $mailer = $this->mailerFactory->create($credentials);

        $user = $this->users->find($userId);
        if ($user->getEmail() === NULL) {
            throw new EmailNotSetException();
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
