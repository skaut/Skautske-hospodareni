<?php

declare(strict_types=1);

namespace App;

use App\AccountancyModule\Factories\ILoginPanelFactory;
use App\Components\LoginPanel;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Auth\IAuthorizator;
use Model\Common\Services\NotificationsCollector;
use Model\UnitService;
use Model\UserService;
use Nette;
use Nette\Application\IResponse;
use Nette\Application\LinkGenerator;
use Nette\Security\Identity;
use Psr\Log\LoggerInterface;
use Skautis\Wsdl\AuthenticationException;
use function array_key_last;
use function assert;
use function explode;
use function sprintf;

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

    /** @var CommandBus */
    protected $commandBus;

    /** @var QueryBus */
    protected $queryBus;

    /** @var IAuthorizator */
    protected $authorizator;

    /** @var ILoginPanelFactory */
    private $loginPanelFactory;

    /** @var NotificationsCollector */
    private $notificationsCollector;

    /** @var LoggerInterface */
    protected $logger;

    /** @var LinkGenerator */
    protected $linkGenerator;

    public function injectAll(
        UserService $userService,
        UnitService $unitService,
        CommandBus $commandBus,
        QueryBus $queryBus,
        IAuthorizator $authorizator,
        ILoginPanelFactory $loginPanelFactory,
        NotificationsCollector $notificationsCollector,
        LoggerInterface $logger,
        LinkGenerator $linkGenerator
    ) : void {
        $this->userService            = $userService;
        $this->unitService            = $unitService;
        $this->commandBus             = $commandBus;
        $this->queryBus               = $queryBus;
        $this->authorizator           = $authorizator;
        $this->loginPanelFactory      = $loginPanelFactory;
        $this->logger                 = $logger;
        $this->notificationsCollector = $notificationsCollector;
        $this->linkGenerator          = $linkGenerator;
    }

    protected function startup() : void
    {
        parent::startup();

        $this->appDir = $this->context->getParameters()['appDir'];

        //adresář s částmi šablon pro použití ve více modulech
        $this->template->setParameters([
            'templateBlockDir' => $this->appDir . '/templateBlocks/',
            'backlink' => $backlink = $this->getParameter('backlink'),
            'testBackground' => $this->context->getParameters()['testBackground'],
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

        $presenterNameParts = explode(':', $this->getName());

        $parameters = $this->context->getParameters();

        $this->template->setParameters([
            'module' => $presenterNameParts[1] ?? null,
            'presenterName' => $presenterNameParts[array_key_last($presenterNameParts)],
            'linkGenerator' => $this->linkGenerator,
            'productionMode' => $parameters['productionMode'],
            'wwwDir' => $parameters['wwwDir'],
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
        } catch (AuthenticationException $ex) {
            $this->getUser()->logout(true);
        }
    }

    public function handleChangeRole(?int $roleId = null) : void
    {
        if ($roleId === null) {
            throw new Nette\Application\BadRequestException();
        }

        $this['loginPanel']->handleChangeRole($roleId);
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

    /**
     * @param IResponse $response
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    protected function shutdown($response) : void
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
