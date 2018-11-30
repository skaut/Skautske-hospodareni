<?php

declare(strict_types=1);

namespace App;

use App\AccountancyModule\SkautisMaintenance;
use Nette;
use Nette\Application\UI\Presenter;
use Skautis\Wsdl\AuthenticationException;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WsdlException;
use Tracy\ILogger;
use function in_array;
use function sprintf;

/**
 * Error presenter.
 */
class ErrorPresenter extends Presenter
{
    /** @var ILogger */
    private $logger;

    private const SKAUTIS_UNAVAILABLE_ERRORS = [
        'Server was unable to process request.',
        'Could not connect to host',
    ];

    public function __construct(ILogger $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    /**
     * @param mixed $exception
     * @throws Nette\Application\AbortException
     */
    public function renderDefault($exception) : void
    {
        if ($exception instanceof SkautisMaintenance) {
            $this->flashMessage('Právě probíhá údržba Skautisu. Po tuto dobu není možné Hospodaření používat', 'danger');
            $this->redirect(':Default:');
        }

        if ($exception instanceof AuthenticationException) {//vypršelo přihlášení do SkautISu
            $this->user->logout(true);
            $this->flashMessage('Vypršelo přihlášení do skautISu', 'danger');
            $this->redirect(':Default:');
        }

        if ($exception instanceof PermissionException) {
            $this->flashMessage($exception->getMessage(), 'danger');
            $this->logger->log($exception, ILogger::EXCEPTION);
            $this->redirect(':Default:');
        }

        if ($exception instanceof WsdlException && $this->isSkautisUnavailable($exception)) {
            $this->flashMessage('Nepodařilo se připojit ke Skautisu. Zkuste to prosím za chvíli nebo zkontrolujte, zda neprobíhá jeho údržba.');
            $this->redirect(':Default:');
        }

        if ($exception instanceof Nette\Application\BadRequestException) {
            $code = $exception->getCode();
            // load template 403.latte or 404.latte or ... 4xx.latte
            $this->setView(in_array($code, [403, 404, 405, 410, 500], true) ? $code : '4xx');
        } else {
            $this->setView('500'); // load template 500.latte
            $this->logger->log(
                sprintf(
                    'userId: %s Msg: %s in %s:%d',
                    $this->user->getId(),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                ),
                ILogger::EXCEPTION
            );
            $this->logger->log($exception, ILogger::EXCEPTION); // and log exception
        }

        if (! $this->isAjax()) {
            return;
        }
        // AJAX request? Note this error in payload.
        $this->payload->error = true;
        $this->sendPayload();
    }

    private function isSkautisUnavailable(WsdlException $exception) : bool
    {
        foreach (self::SKAUTIS_UNAVAILABLE_ERRORS as $message) {
            if (Nette\Utils\Strings::startsWith($exception->getMessage(), $message)) {
                return true;
            }
        }

        return false;
    }
}
