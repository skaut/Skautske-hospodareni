<?php

declare(strict_types=1);

namespace App;

use App\Components\DarkModeToggle;
use App\Components\Factories\IDarkModeToggleFactory;
use App\Components\Factories\ILoginPanelFactory;
use App\Components\LoginPanel;
use App\Model\Auth\IAuthorizator;
use App\Model\Auth\Resources\Admin;
use App\Model\Auth\Resources\InvoiceAccess;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\NotificationsCollector;
use App\Model\Common\Services\QueryBus;
use App\Model\Unit\UnitService;
use App\Model\User\UserPreferencesService;
use App\Model\User\UserService;
use Contributte\MenuControl\IMenuItem;
use Contributte\MenuControl\MenuContainer;
use Contributte\MenuControl\UI\IMenuComponentFactory;
use Contributte\MenuControl\UI\MenuComponent;
use LogicException;
use Nette\Application\BadRequestException;
use Nette\Application\LinkGenerator;
use Nette\Application\Response;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Http\IResponse;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use Psr\Log\LoggerInterface;
use Skautis\Wsdl\AuthenticationException;

use function array_key_last;
use function dirname;
use function explode;
use function in_array;
use function is_file;
use function is_numeric;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;

/** @property DefaultTemplate $template */
abstract class BasePresenter extends Presenter
{
    private const SESSION_KEEP_ALIVE_INTERVAL_MS = 1_200_000;

    private const PUBLIC_ACTIONS = [
        'Default' => ['default', 'about', 'reinforcement'],
    ];

    private const AUTH_ACTIONS = ['ajax', 'default', 'logonskautis', 'logoutsis', 'skautis', 'skautislogout'];

    protected UserService $userService;

    protected UnitService $unitService;

    private string $appDir;

    private ?int $unitId = null;

    protected CommandBus $commandBus;

    protected QueryBus $queryBus;

    protected IAuthorizator $authorizator;

    private ILoginPanelFactory $loginPanelFactory;

    private IDarkModeToggleFactory $darkModeToggleFactory;

    private NotificationsCollector $notificationsCollector;

    protected LoggerInterface $logger;

    protected LinkGenerator $linkGenerator;

    private Context $appContext;

    private IMenuComponentFactory $menuComponentFactory;

    private MenuContainer $menuContainer;

    private UserPreferencesService $userPreferences;

    public function injectAll(
        UserService $userService,
        UnitService $unitService,
        CommandBus $commandBus,
        QueryBus $queryBus,
        IAuthorizator $authorizator,
        ILoginPanelFactory $loginPanelFactory,
        IDarkModeToggleFactory $darkModeToggleFactory,
        NotificationsCollector $notificationsCollector,
        LoggerInterface $logger,
        LinkGenerator $linkGenerator,
        Context $appContext,
        IMenuComponentFactory $menuComponentFactory,
        MenuContainer $menuContainer,
        UserPreferencesService $userPreferences,
    ): void {
        $this->userService = $userService;
        $this->unitService = $unitService;
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
        $this->authorizator = $authorizator;
        $this->loginPanelFactory = $loginPanelFactory;
        $this->darkModeToggleFactory = $darkModeToggleFactory;
        $this->logger = $logger;
        $this->notificationsCollector = $notificationsCollector;
        $this->linkGenerator = $linkGenerator;
        $this->appContext = $appContext;
        $this->menuComponentFactory = $menuComponentFactory;
        $this->menuContainer = $menuContainer;
        $this->userPreferences = $userPreferences;
    }

    protected function startup(): void
    {
        parent::startup();

        $this->appDir = $this->appContext->getAppDir();

        // adresář s částmi šablon pro použití ve více modulech
        $this->template->setParameters([
            'templateBlockDir' => $this->appDir.'/templateBlocks/',
            'backlink' => $backlink = $this->getParameter('backlink'),
            'testBackground' => $this->appContext->shouldShowTestBackground(),
            'environmentLabel' => $this->appContext->getEnvironmentLabel(),
            'environmentColor' => $this->appContext->getEnvironmentColor(),
        ]);

        if ($this->getUser()->isLoggedIn()) {
            $this->validateCurrentLogin();
        }

        if (! $this->getUser()->isLoggedIn() && ! $this->isPublicRequest()) {
            $backlink = $this->storeRequest('+ 3 days');
            if ($this->isAjax()) {
                $this->forward(':Auth:ajax', ['backlink' => $backlink]);
            }

            $this->redirect(':Default:', ['backlink' => $backlink]);
        }

        if ($this->getUser()->isLoggedIn() && $backlink !== null) {
            $this->restoreRequest($backlink);
        }
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        [$module, $presenterName] = $this->resolveTemplateSection();
        $sessionKeepAliveEnabled = $this->getUser()->isLoggedIn()
            && $this->userPreferences->shouldExtendSkautisLogin();

        $this->template->setParameters([
            'module' => $module,
            'presenterName' => $presenterName,
            'linkGenerator' => $this->linkGenerator,
            'navigationBreadcrumbs' => $this->resolveNavigationBreadcrumbs($module),
            'productionMode' => $this->appContext->isProduction(),
            'wwwDir' => $this->appContext->getWwwDir(),
            'currentUrl' => (string) $this->getHttpRequest()->getUrl(),
            'canAccessAdmin' => $this->authorizator->isAllowed(Admin::ACCESS, null),
            'canAccessInvoiceAccess' => $this->authorizator->isAllowed(InvoiceAccess::ACCESS, null),
            'showPageHelp' => $this->userPreferences->shouldShowHelp(),
            'sessionKeepAliveEnabled' => $sessionKeepAliveEnabled,
            'sessionKeepAliveInterval' => self::SESSION_KEEP_ALIVE_INTERVAL_MS,
            'sessionKeepAliveUrl' => $sessionKeepAliveEnabled
                ? $this->linkGenerator->link('SessionKeepAlive:default')
                : null,
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

    public function handleChangeRole(?int $roleId = null): void
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

    protected function createComponentDarkModeToggle(): DarkModeToggle
    {
        return $this->darkModeToggleFactory->create();
    }

    protected function createComponentMainMenu(): MenuComponent
    {
        return $this->menuComponentFactory->create('main');
    }

    protected function createComponentPaymentsMenu(): MenuComponent
    {
        return $this->menuComponentFactory->create('payments');
    }

    protected function createComponentSettingsMenu(): MenuComponent
    {
        return $this->menuComponentFactory->create('settings');
    }

    protected function createComponentAdminMenu(): MenuComponent
    {
        return $this->menuComponentFactory->create('admin');
    }

    protected function createComponentTravelMenu(): MenuComponent
    {
        return $this->menuComponentFactory->create('travel');
    }

    protected function updateUserAccess(): void
    {
        $identity = $this->user->getIdentity();

        if (! $identity instanceof SimpleIdentity) {
            throw new LogicException('Assertion failed.');
        }
        $identity->access = $this->userService->getAccessArrays($this->unitService);
    }

    public function getLoggedInUserId(): int
    {
        $identity = $this->getUser()->getIdentity();
        if (! $this->isValidUserIdentity($identity)) {
            throw new BadRequestException('User identity is not valid', IResponse::S403_Forbidden);
        }

        return (int) $identity->getId();
    }

    private function validateCurrentLogin(): void
    {
        if (! $this->isValidUserIdentity($this->getUser()->getIdentity())) {
            $this->logoutInvalidUser();

            return;
        }

        try {
            if ($this->userService->isLoggedIn()) { // prodluzuje přihlášení při každém požadavku
                return;
            }
        } catch (AuthenticationException $e) {
            if (! $this->isSkautisLogoutRequest()) { // pokud jde o odhlaseni, tak to nevadi
                throw $e;
            }
        }

        $this->logoutInvalidUser();
    }

    private function logoutInvalidUser(): void
    {
        $this->getUser()->logout(true);
        if ($this->isPublicRequest()) {
            return;
        }

        $backlink = $this->storeRequest('+ 3 days');
        if ($this->isAjax()) {
            $this->forward(':Auth:ajax', ['backlink' => $backlink]);
        }

        $this->redirect(':Default:', ['backlink' => $backlink]);
    }

    private function isValidUserIdentity(?IIdentity $identity): bool
    {
        return $identity !== null && is_numeric($identity->getId());
    }

    private function isSkautisLogoutRequest(): bool
    {
        return $this->getName() === 'Auth' && strtolower($this->getAction()) === 'skautislogout';
    }

    private function isPublicRequest(): bool
    {
        if ($this->getName() === 'Auth') {
            return in_array(strtolower($this->getAction()), self::AUTH_ACTIONS, true);
        }

        return in_array($this->getAction(), self::PUBLIC_ACTIONS[$this->getName()] ?? [], true);
    }

    /**
     * Returns OFFICIAL unit ID.
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

    /** @return string[] */
    public function formatTemplateFiles(): array
    {
        if (! $this->usesPresentationDirectory()) {
            return parent::formatTemplateFiles();
        }

        $presenterDir = dirname((string) static::getReflection()->getFileName());

        return [
            $presenterDir.'/'.$this->view.'.latte',
            ...parent::formatTemplateFiles(),
        ];
    }

    /** @return string[] */
    public function formatLayoutTemplateFiles(): array
    {
        if (preg_match('#/|\\\\#', (string) $this->layout)) {
            return [(string) $this->layout];
        }

        if (! $this->usesPresentationDirectory()) {
            return parent::formatLayoutTemplateFiles();
        }

        $layout = $this->layout ?: 'layout';
        $presenterDir = dirname((string) static::getReflection()->getFileName());
        $moduleDir = dirname($presenterDir);
        $presentationRoot = dirname($moduleDir);

        $files = [
            $presenterDir.'/@'.$layout.'.latte',
        ];

        while ($moduleDir !== $presentationRoot && is_file($moduleDir.'/@'.$layout.'.latte') === false) {
            $files[] = $moduleDir.'/@'.$layout.'.latte';
            $moduleDir = dirname($moduleDir);
        }

        $files[] = $moduleDir.'/@'.$layout.'.latte';

        return [
            ...$files,
            ...parent::formatLayoutTemplateFiles(),
            $this->appDir.'/templates/@'.$layout.'.latte',
        ];
    }

    /** @return array{0: string|null, 1: string} */
    private function resolveTemplateSection(): array
    {
        $presenterNameParts = explode(':', $this->getName());
        $presenterName = $presenterNameParts[array_key_last($presenterNameParts)];

        if (($presenterNameParts[0] ?? null) === 'Accountancy') {
            return [$presenterNameParts[1] ?? null, $presenterName];
        }

        return [$presenterNameParts[0] ?? null, $presenterName];
    }

    private function usesPresentationDirectory(): bool
    {
        $file = static::getReflection()->getFileName();

        return $file !== false && str_contains(str_replace('\\', '/', $file), '/app/Presentation/');
    }

    /** @return array<int, array{title: string, link: string|null, current: bool}> */
    private function resolveNavigationBreadcrumbs(?string $module): array
    {
        $menuName = match ($module) {
            'Payment', 'Payments' => 'payments',
            default => null,
        };

        if ($menuName === null) {
            return [];
        }

        $menu = $this->menuContainer->getMenu($menuName);
        $menu->setActivePresenter($this);

        $rootItem = $menu->findActiveItem();
        if (! $rootItem instanceof IMenuItem) {
            return [];
        }

        $activeItem = $rootItem->findActiveItem();

        $items = [[
            'title' => $rootItem->getRealTitle(),
            'link' => $activeItem instanceof IMenuItem ? $rootItem->getRealLink() : null,
            'current' => ! $activeItem instanceof IMenuItem,
        ]];

        if ($activeItem instanceof IMenuItem) {
            $items[] = [
                'title' => $activeItem->getRealTitle(),
                'link' => null,
                'current' => true,
            ];
        }

        return $items;
    }
}
