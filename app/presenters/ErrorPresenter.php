<?php

namespace App;

use Nette,
    Tracy\ILogger;

/**
 * Error presenter.
 */
class ErrorPresenter extends \Nette\Application\UI\Presenter
{

    /** @var ILogger */
    private $logger;

    public function __construct(ILogger $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    /**
     * @param  Exception
     * @return void
     */
    public function renderDefault($exception) : void
    {
        if ($exception instanceof Nette\Application\BadRequestException) {
            $code = $exception->getCode();
            // load template 403.latte or 404.latte or ... 4xx.latte
            $this->setView(in_array($code, [403, 404, 405, 410, 500]) ? $code : '4xx');
        } elseif ($exception instanceof \Skautis\Wsdl\PermissionException) {
            $this->flashMessage($exception->getMessage(), "danger");
            $this->redirect(":Default:");
        } elseif ($exception instanceof \Skautis\Wsdl\AuthenticationException) {//vypršelo přihlášení do SkautISu
            $this->user->logout(TRUE);
            $this->flashMessage("Vypršelo přihlášení do skautISu", "danger");
            $this->redirect(":Default:");
        } else {
            $this->setView('500'); // load template 500.latte
            $this->logger->log("userId: " . $this->user->getId() . " Msg: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", ILogger::EXCEPTION);
            $this->logger->log($exception, ILogger::EXCEPTION); // and log exception
        }

        if ($this->isAjax()) { // AJAX request? Note this error in payload.
            $this->payload->error = TRUE;
            $this->terminate();
        }
    }

}
