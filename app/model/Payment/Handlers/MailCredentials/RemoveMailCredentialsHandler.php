<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\MailCredentials;

use Model\Payment\Commands\RemoveMailCredentials;
use Model\Payment\MailCredentialsNotFound;
use Model\Payment\Repositories\IMailCredentialsRepository;

class RemoveMailCredentialsHandler
{
    private IMailCredentialsRepository $credentials;

    public function __construct(IMailCredentialsRepository $credentials)
    {
        $this->credentials = $credentials;
    }

    public function __invoke(RemoveMailCredentials $command) : void
    {
        try {
            $credentials = $this->credentials->find($command->getId());
            $this->credentials->remove($credentials);
        } catch (MailCredentialsNotFound $e) {
            // fail silently
        }
    }
}
