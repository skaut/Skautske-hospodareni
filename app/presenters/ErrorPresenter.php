<?php

declare(strict_types=1);

namespace App;

use App\AccountancyModule\SkautisMaintenance;
use eGen\MessageBus\Bus\CommandBus;
use Model\Cashbook\Commands\Cashbook\SelectFirstActiveRole;
use Model\Skautis\Exception\MissingCurrentRole;
use Model\Unit\UserHasNoUnit;
use Model\User\Exception\UserHasNoRole;
use Nette;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Psr\Log\LoggerInterface;
use Skautis\Wsdl\AuthenticationException;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WsdlException;
use function in_array;

class ErrorPresenter extends Presenter
{
    private LoggerInterface $logger;

    protected CommandBus $commandBus;

    private const SKAUTIS_UNAVAILABLE_ERRORS = [
        'Server was unable to process request.',
        'Could not connect to host',
        'Service Unavailable',
    ];

    public function __construct(LoggerInterface $logger, CommandBus $commandBus)
    {
        parent::__construct();
        $this->logger     = $logger;
        $this->commandBus = $commandBus;
    }

    /**
     * @param mixed $exception
     *
     * @throws Nette\Application\AbortException
     */
    public function renderDefault($exception, ?Request $request = null) : void
    {
        if ($exception instanceof SkautisMaintenance || $exception instanceof WsdlException && $this->isSkautisUnavailable($exception)) {
            $this->flashMessage('Nepodařilo se připojit ke Skautisu. Zkuste to prosím za chvíli nebo zkontrolujte, zda neprobíhá jeho údržba.', 'danger');
            $this->redirect(':Default:');
        }

        if ($exception instanceof AuthenticationException) {//vypršelo přihlášení do SkautISu
            $this->getUser()->logout(true);
            $this->flashMessage('Vypršelo přihlášení do skautISu', 'danger');
            $this->redirect(':Default:');
        }

        if ($exception instanceof PermissionException) {
            $this->flashMessage($exception->getMessage(), 'danger');
            $this->redirect(':Default:');
        }

        if ($exception instanceof MissingCurrentRole) {
            try {
                $this->commandBus->handle(new SelectFirstActiveRole());
                $this->flashMessage('Chyběla aktivní role, byl jste automaticky přehlášen na jinou roli.', 'danger');
                $this->forward($request);
            } catch (UserHasNoRole $exc) {
                $this->setView('noRole');
            }
        }

        if ($exception instanceof UserHasNoUnit) {
            $this->setView('noUnit');
        } elseif ($exception instanceof Nette\Application\BadRequestException) {
            $code = $exception->getCode();
            // load template 403.latte or 404.latte or ... 4xx.latte
            $this->setView(in_array($code, [403, 404, 405, 410, 500], true) ? $code : '4xx');
        } else {
            $this->setView('500'); // load template 500.latte
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
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
