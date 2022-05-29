<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use Model\Common\UnitId;
use Model\Skautis\SkautisMaintenanceChecker;
use Model\UserService;
use Nette\Security\SimpleIdentity;
use stdClass;

use function array_keys;
use function assert;

abstract class BasePresenter extends \App\BasePresenter
{
    protected ?string $backlink = null;

    /**
     * id volane v url, vetsinou id akce
     */
    protected ?int $aid = null;

    protected UnitId $unitId;

    /**
     * je akci možné upravovat?
     */
    protected bool $isEditable;

    private SkautisMaintenanceChecker $skautisMaintenanceChecker;

    /** @var string camp, event, unit */
    public string $type;

    public function injectSkautisMaintenanceChecker(SkautisMaintenanceChecker $checker): void
    {
        $this->skautisMaintenanceChecker = $checker;
    }

    protected function startup(): void
    {
        parent::startup();

        if ($this->skautisMaintenanceChecker->isMaintenance()) {
            throw new SkautisMaintenance();
        }

        if (! $this->getUser()->isLoggedIn()) {
            $this->backlink = $this->storeRequest('+ 3 days');
            if ($this->isAjax()) {
                $this->forward(':Auth:ajax', ['backlink' => $this->backlink]);
            } else {
                $this->redirect(':Default:', ['backlink' => $this->backlink]);
            }
        }

        $aid       = $this->getParameter('aid');
        $this->aid = $aid === null ? null : (int) $aid;
        if ($this->aid !== null) { // Parameters aren't auto-casted to int
            $this->aid = (int) $this->aid;
        }

        $unitId       = $this->getParameter('unitId', null);
        $this->unitId = new UnitId($unitId !== null ? (int) $unitId : $this->unitService->getUnitId());

        $this->userService->updateLogoutTime();
    }

    /**
     * @param string $message
     */
    // phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    public function flashMessage($message, string $type = 'info'): stdClass
    {
        $this->redrawControl('flash');

        return parent::flashMessage($message, $type);
    }

    public function getCurrentUnitId(): UnitId
    {
        return $this->unitId;
    }

    /**
     * @return int[]
     */
    protected function getEditableUnitIds(): array
    {
        $identity = $this->getUser()->getIdentity();

        if ($identity === null) {
            return [];
        }

        assert($identity instanceof SimpleIdentity);

        /** @var array<int, mixed> $editableUnits */
        $editableUnits = $identity->access[UserService::ACCESS_EDIT];

        return array_keys($editableUnits);
    }

    public function renderAccessDenied(): void
    {
        $this->template->setFile(__DIR__ . '/../templates/accessDenied.latte');
    }
}
