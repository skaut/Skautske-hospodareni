<?php

declare(strict_types=1);

namespace App;

use App\AccountancyModule\Factories\ILoginPanelFactory;
use App\Components\LoginPanel;
use Model\Auth\IAuthorizator;
use Model\Common\Services\CommandBus;
use Model\Common\Services\NotificationsCollector;
use Model\Common\Services\QueryBus;
use Model\UnitService;
use Model\UserService;
use Nette\Application\BadRequestException;
use Nette\Application\LinkGenerator;
use Nette\Application\Response;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Security\SimpleIdentity;
use Psr\Log\LoggerInterface;
use Skautis\Wsdl\AuthenticationException;

use function array_key_last;
use function assert;
use function explode;
use function sprintf;

/** @property-read DefaultTemplate $template */
abstract class BasePresenter extends Presenter
{
    protected UserService $userService;

    protected UnitService $unitService;

    private string $appDir;

    private int|null $unitId = null;

    protected CommandBus $commandBus;

    protected QueryBus $queryBus;

    protected IAuthorizator $authorizator;

    private ILoginPanelFactory $loginPanelFactory;

    private NotificationsCollector $notificationsCollector;

    protected LoggerInterface $logger;

    protected LinkGenerator $linkGenerator;

    private Context $appContext;

    public function injectAll(
        UserService $userService,
        UnitService $unitService,
        CommandBus $commandBus,
        QueryBus $queryBus,
        IAuthorizator $authorizator,
        ILoginPanelFactory $loginPanelFactory,
        NotificationsCollector $notificationsCollector,
        LoggerInterface $logger,
        LinkGenerator $linkGenerator,
        Context $appContext,
    ): void {
        $this->userService            = $userService;
        $this->unitService            = $unitService;
        $this->commandBus             = $commandBus;
        $this->queryBus               = $queryBus;
        $this->authorizator           = $authorizator;
        $this->loginPanelFactory      = $loginPanelFactory;
        $this->logger                 = $logger;
        $this->notificationsCollector = $notificationsCollector;
        $this->linkGenerator          = $linkGenerator;
        $this->appContext             = $appContext;
    }

    protected function startup(): void
    {
        parent::startup();

        $this->appDir = $this->appContext->getAppDir();

        //adresář s částmi šablon pro použití ve více modulech
        $this->template->setParameters([
            'templateBlockDir' => $this->appDir . '/templateBlocks/',
            'backlink' => $backlink = $this->getParameter('backlink'),
            'testBackground' => $this->appContext->shouldShowTestBackground(),
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

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $presenterNameParts = explode(':', $this->getName());

        $this->template->setParameters([
            'module' => $presenterNameParts[1] ?? null,
            'presenterName' => $presenterNameParts[array_key_last($presenterNameParts)],
            'linkGenerator' => $this->linkGenerator,
            'productionMode' => $this->appContext->isProduction(),
            'wwwDir' => $this->appContext->getWwwDir(),
        ]);

        if (! $this->getUser()->isLoggedIn()) {
            return;
        }

        try {
            $this->template->setParameters([
                'currentUnitId' => $this->unitService->getUnitId(),
                'myRoles' => $this->userService->getAllSkautisRoles(),
                'myRole' => $this->userService->getRoleId(),
            ]);
        } catch (AuthenticationException) {
            $this->getUser()->logout(true);
        }
    }

    public function handleChangeRole(int|null $roleId = null): void
    {
        if ($roleId === null) {
            throw new BadRequestException();
        }

        $this['loginPanel']->handleChangeRole($roleId);
    }

    protected function createComponentLoginPanel(): LoginPanel
    {
        return $this->loginPanelFactory->create();
    }

    protected function updateUserAccess(): void
    {
        $identity = $this->user->getIdentity();

        assert($identity instanceof SimpleIdentity);

        $identity->access = $this->userService->getAccessArrays($this->unitService);
    }

    /**
     * Returns OFFICIAL unit ID
     */
    public function getUnitId(): int
    {
        if ($this->unitId === null) {
            $this->unitId = $this->unitService->getOfficialUnit()->getId();
        }

        return $this->unitId;
    }

    protected function shutdown(Response $response): void
    {
        foreach ($this->notificationsCollector->popNotifications() as [$type, $message, $count]) {
            if ($type === NotificationsCollector::ERROR) {
                $type = 'danger';
            }

            if ($count > 1) {
                $message = sprintf('%s (%d)', $message, $count);
            }

            $this->flashMessage($message, $type);
        }
    }
}
