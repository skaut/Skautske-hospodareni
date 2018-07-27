<?php

declare(strict_types=1);

namespace App\AccountancyModule;

use Model\Skautis\SkautisMaintenanceChecker;

abstract class BasePresenter extends \App\BasePresenter
{
    /** @var string|null */
    protected $backlink;

    /**
     * id volane v url, vetsinou id akce
     *
     * @var int|null
     */
    protected $aid;

    /**
     * je akci možné upravovat?
     *
     * @var bool
     */
    protected $isEditable;

    /** @var SkautisMaintenanceChecker */
    private $skautisMaintenanceChecker;

    /** @var string camp, event, unit */
    public $type;

    public function injectSkautisMaintenanceChecker(SkautisMaintenanceChecker $checker) : void
    {
        $this->skautisMaintenanceChecker = $checker;
    }

    protected function startup() : void
    {
        parent::startup();

        if ($this->skautisMaintenanceChecker->isMaintenance()) {
            throw new SkautisMaintenance();
        }

        $this->aid = $this->getParameter('aid', null);
        if ($this->aid !== null) { // Parameters aren't auto-casted to int
            $this->aid = (int) $this->aid;
        }

        if (! $this->user->isLoggedIn()) {
            $this->backlink = $this->storeRequest('+ 3 days');
            if ($this->isAjax()) {
                $this->forward(':Auth:ajax', ['backlink' => $this->backlink]);
            } else {
                $this->redirect(':Default:', ['backlink' => $this->backlink]);
            }
        }

        $this->userService->updateLogoutTime();
    }

    /**
     * {@inheritDoc}
     */
    public function flashMessage($message, $type = 'info') : \stdClass
    {
        $this->redrawControl('flash');
        return parent::flashMessage($message, $type);
    }

    /**
     * Returns current unit ID (e.g oddíl)
     */
    public function getCurrentUnitId() : int
    {
        return (int) $this->aid;
    }
}
