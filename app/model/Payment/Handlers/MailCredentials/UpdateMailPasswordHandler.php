<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\MailCredentials;

use Model\Payment\Commands\UpdateMailPassword;
use Model\Payment\Repositories\IMailCredentialsRepository;

class UpdateMailPasswordHandler
{
    private IMailCredentialsRepository $credentials;

    public function __construct(IMailCredentialsRepository $credentials)
    {
        $this->credentials = $credentials;
    }

    public function __invoke(UpdateMailPassword $command) : void
    {
        $credentials = $this->credentials->find($command->getId());
        $credentials->setPassword($command->getPassword());
        $this->credentials->save($credentials);
    }
}
