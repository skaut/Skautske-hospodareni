<?php

declare(strict_types=1);

namespace App;

use App\AccountancyModule\Factories\ILoginPanelFactory;
use App\Components\LoginPanel;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Auth\IAuthorizator;
use Model\UnitService;
use Model\UserService;
use Nette;
use Nette\Security\Identity;
use Psr\Log\LoggerInterface;
use Skautis\Wsdl\AuthenticationException;
use WebLoader\Nette as WebLoader;
use function assert;
use function explode;

/**
 * @property-read Nette\Bridges\ApplicationLatte\Template $template
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @var UserService */
    protected $userService;

    /** @var UnitService */
    protected $unitService;

    /** @var string */
    private $appDir;

    /** @var int */
    private $unitId;

    /** @var WebLoader\LoaderFactory */
    private $webLoader;

    /** @var CommandBus */
    protected $commandBus;

    /** @var QueryBus */
    protected $queryBus;

    /** @var IAuthorizator */
    protected $authorizator;

    /** @var ILoginPanelFactory */
    private $loginPanelFactory;

    /** @var LoggerInterface */
    protected $logger;

    public function injectAll(
        WebLoader\LoaderFactory $webLoader,
        UserService $userService,
        UnitService $unitService,
        CommandBus $commandBus,
        QueryBus $queryBus,
        IAuthorizator $authorizator,
        ILoginPanelFactory $loginPanelFactory,
        LoggerInterface $logger
    ) : void {
        $this->webLoader         = $webLoader;
        $this->userService       = $userService;
        $this->unitService       = $unitService;
        $this->commandBus        = $commandBus;
        $this->queryBus          = $queryBus;
        $this->authorizator      = $authorizator;
        $this->loginPanelFactory = $loginPanelFactory;
        $this->logger            = $logger;
    }

    protected function startup() : void
    {
        parent::startup();

        $this->appDir = $this->context->getParameters()['appDir'];

        //adresář s částmi šablon pro použití ve více modulech
        $this->template->setParameters([
            'templateBlockDir' => $this->appDir . '/templateBlocks/',
            'backlink' => $backlink = $this->getParameter('backlink'),
        ]);

        if ($this->getUser()->isLoggedIn() && $backlink !== null) {
            $this->restoreRequest($backlink);
        }

        try {
            if ($this->getUser()->isLoggedIn()) { //prodluzuje přihlášení při každém požadavku
                $this->userService->isLoggedIn();
            }
        } catch (AuthenticationException $e) {
            if ($this->getName() !== 'Auth' || $this->params['action'] !== 'skautisLogout') { //pokud jde o odhlaseni, tak to nevadi
                throw $e;
            }
        }
    }

    protected function beforeRender() : void
    {
        parent::beforeRender();

        $this->template->setParameters(['module' => explode(':', $this->getName())[1] ?? null]);

        if (! $this->getUser()->isLoggedIn()) {
            return;
        }

        try {
            $this->template->setParameters([
                'currentUnitId' => $this->unitService->getUnitId(),
                'myRoles' => $this->userService->getAllSkautisRoles(),
                'myRole' => $this->userService->getRoleId(),
            ]);
        } catch (AuthenticationException $ex) {
            $this->getUser()->logout(true);
        }
    }

    public function handleChangeRole(?int $roleId = null) : void
    {
        if ($roleId === null) {
            throw new Nette\Application\BadRequestException();
        }

        $this->userService->updateSkautISRole($roleId);
        $this->updateUserAccess();
        $this->redirect('this');
    }

    protected function createComponentCss() : WebLoader\CssLoader
    {
        $control = $this->webLoader->createCssLoader('default');
        $control->setMedia('screen');

        return $control;
    }

    protected function createComponentJs() : WebLoader\JavaScriptLoader
    {
        return $this->webLoader->createJavaScriptLoader('default');
    }

    protected function createComponentLoginPanel() : LoginPanel
    {
        return $this->loginPanelFactory->create();
    }

    protected function updateUserAccess() : void
    {
        $identity = $this->user->getIdentity();

        assert($identity instanceof Identity);

        $identity->access = $this->userService->getAccessArrays($this->unitService);
    }

    /**
     * Returns OFFICIAL unit ID
     */
    public function getUnitId() : int
    {
        if ($this->unitId === null) {
            $this->unitId = $this->unitService->getOfficialUnit()->getId();
        }

        return $this->unitId;
    }
}
