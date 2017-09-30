<?php

namespace Model;

use Model\DTO\Payment\Mail;
use Model\DTO\Payment\MailFactory;
use Model\Mail\IMailerFactory;
use Model\Payment\EmailNotSetException;
use Model\Payment\MailCredentials;
use Model\Payment\MailCredentialsNotFound;
use Model\Payment\Repositories\IMailCredentialsRepository;
use Model\Payment\Repositories\IUserRepository;
use Model\Services\TemplateFactory;
use Nette\Mail\Message;
use Nette\Mail\SmtpException;

/**
 * @author Hána František
 */
class MailService
{

    /** @var MailTable */
    private $table;

    /** @var UnitService */
    private $units;

    /** @var IUserRepository */
    private $users;

    /** @var IMailerFactory */
    private $mailerFactory;

    /** @var TemplateFactory */
    private $templateFactory;

    /** @var IMailCredentialsRepository */
    private $credentials;

    public function __construct(
        MailTable $table,
        UnitService $units,
        IUserRepository $users,
        IMailerFactory $mailerFactory,
        TemplateFactory $templateFactory,
        IMailCredentialsRepository $credentials
    )
    {
        $this->table = $table;
        $this->units = $units;
        $this->users = $users;
        $this->mailerFactory = $mailerFactory;
        $this->templateFactory = $templateFactory;
        $this->credentials = $credentials;
    }

    public function get(int $id): ?Mail
    {
        try {
            return MailFactory::create(
                $this->credentials->find($id)
            );
        } catch (MailCredentialsNotFound $e) {
            return NULL;
        }
    }

    public function getAll(int $unitId) : array
    {
        $mails = $this->findForUnit($unitId);
        return array_map([MailFactory::class, 'create'], $mails);
    }

    public function getPairs(int $unitId) : array
    {
        $pairs = [];
        foreach($this->findForUnit($unitId) as $credentials) {
            $pairs[$credentials->getId()] = $credentials->getUsername();
        }

        return $pairs;
    }

    /**
     * @param int $unitId
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $secure
     * @param int $userId
     * @throws EmailNotSetException
     * @throws SmtpException
     */
    public function addSmtp(int $unitId, string $host, string $username, string $password, string $secure, int $userId): void
    {
//        $this->trySendViaSmtp([
//            'host' => $host,
//            'username' => $username,
//            'password' => $password,
//            'secure' => $secure,
//        ], $userId);

        $this->table->addSmtp($unitId, $host, $username, $password, $secure);
    }

    public function removeSmtp($unitId, $id): void
    {
        $this->table->removeSmtp($unitId, $id);
    }

    /**
     * @param int $unitId
     * @return MailCredentials[]
     */
    private function findForUnit(int $unitId): array
    {
        $units = [$unitId, $this->units->getOficialUnit($unitId)->ID];
        $byUnit = $this->credentials->findByUnits($units);

        return array_merge(...$byUnit);
    }

    /**
     * Send test email to user who tries to add SMTP
     * @param array $credentials
     * @param int $userId
     * @throws EmailNotSetException
     * @throws SmtpException
     */
    private function trySendViaSmtp(array $credentials, int $userId): void
    {
        $mailer = $this->mailerFactory->create($credentials);

        $user = $this->users->find($userId);
        if ($user->getEmail() === NULL) {
            throw new EmailNotSetException();
        }

        unset($credentials['password']);
        $template = $this->templateFactory->create(TemplateFactory::EMAILS_DIRECTORY . '/smtpAdded.latte', $credentials);

        $mail = new Message();
        $mail->setSubject('Nový email v Hospodaření')
            ->setFrom('platby@skauting.cz', 'Skautské Hospodaření')// email gets rewritten on SMTP
            ->addTo($user->getEmail(), $user->getName())
            ->setHtmlBody($template);

        $mailer->send($mail);
    }

}
