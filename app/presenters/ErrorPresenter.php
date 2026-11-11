<?php

declare(strict_types=1);

namespace App;

use App\Model\Cashbook\Commands\Cashbook\SelectFirstActiveRole;
use App\Model\Common\Services\CommandBus;
use App\Model\Skautis\Exception\MissingCurrentRole;
use App\Model\Skautis\SkautisErrors;
use App\Model\Unit\UserHasNoUnit;
use App\Model\User\Exception\UserHasNoRole;
use LogicException;
use Nette;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Psr\Log\LoggerInterface;
use Skaut\Skautis\Wsdl\AuthenticationException;
use Skaut\Skautis\Wsdl\PermissionException;
use Skaut\Skautis\Wsdl\WsdlException;

use function in_array;

class ErrorPresenter extends Presenter
{
    /** Shown when skautIS did not answer or failed; the acceptance tests recognise a skautIS outage by it. */
    public const SKAUTIS_UNAVAILABLE_MESSAGE = 'Nepodařilo se připojit ke Skautisu. Zkuste to prosím za chvíli nebo zkontrolujte, zda neprobíhá jeho údržba.';

    public function __construct(private LoggerInterface $logger, protected CommandBus $commandBus)
    {
        parent::__construct();
    }

    /** @throws Nette\Application\AbortException */
    public function renderDefault(mixed $exception, ?Request $request = null): void
    {
        if ($exception instanceof SkautisMaintenance || $exception instanceof WsdlException && $this->isSkautisUnavailable($exception)) {
            $this->flashMessage(self::SKAUTIS_UNAVAILABLE_MESSAGE, 'danger');
            $this->redirect(':Default:');
        }

        if ($exception instanceof AuthenticationException) {// vypršelo přihlášení do SkautISu
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
                $this->forward($request ?? throw new LogicException('Chybí původní požadavek.'));
            } catch (UserHasNoRole) {
                $this->setView('noRole');
            }
        }

        if ($exception instanceof UserHasNoUnit) {
            $this->setView('noUnit');
        } elseif ($exception instanceof Nette\Application\BadRequestException) {
            $code = $exception->getCode();
            // Only a denial written for the user is shown; other messages are technical.
            $this->template->setParameters(['reason' => $exception instanceof AccessDenied ? $exception->getMessage() : null]);
            // load template 403.latte or 404.latte or ... 4xx.latte
            $this->setView(in_array($code, [403, 404, 405, 410, 500], true) ? (string) $code : '4xx');
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

    private function isSkautisUnavailable(WsdlException $exception): bool
    {
        return SkautisErrors::isNoAnswer($exception) || SkautisErrors::isServerError($exception);
    }
}
