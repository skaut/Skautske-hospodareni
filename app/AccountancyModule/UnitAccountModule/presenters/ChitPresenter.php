<?php

namespace App\AccountancyModule\UnitAccountModule;

use App\Forms\BaseForm;
use Model\BudgetService;
use Model\Cashbook\CashbookService;
use Model\Cashbook\Commands\Cashbook\LockCashbook;
use Model\Cashbook\Commands\Cashbook\LockChit;
use Model\Cashbook\Commands\Cashbook\UnlockChit;
use Model\Cashbook\ObjectType;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ChitPresenter extends BasePresenter
{

    public $chits;
    public $info;

    /** @persistent */
    public $onlyUnlocked = 1;

    /** @var BudgetService */
    private $budgetService;

    /** @var CashbookService */
    private $cashbookService;

    public function __construct(BudgetService $budgetService, CashbookService $cashbookService)
    {
        parent::__construct();
        $this->budgetService = $budgetService;
        $this->cashbookService = $cashbookService;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->template->onlyUnlocked = $this->onlyUnlocked;
        $oficialUnit = $this->unitService->getOficialUnit($this->aid);
        if ($oficialUnit->ID != $this->aid) {
            $this->flashMessage("Přehled paragonů je dostupný jen pro organizační jednotky.");
            $this->redirect("this", ["aid" => $oficialUnit->ID]);
        }
    }

    public function actionDefault($year = NULL) : void
    {
        $this->info = [];
        foreach ($this->unitService->getReadUnits($this->getUser()) as $ik => $iu) {
            $this->info['unit'][$ik] = (array)$iu;
        }
        $eventService = $this->context->getService("eventService");
        $campService = $this->context->getService("campService");

        $this->info['event'] = $eventService->event->getAll($this->year);
        $this->info['camp'] = $campService->event->getAll($this->year);

        $categories = $this->budgetService->getCategoriesLeaf($this->aid);
        if (empty($categories['in']) && empty($categories['out'])) {
            $this->template->disableForm = TRUE;
        }

        $this->chits = [];
        //formulář pro kategorie, to potrebuje drive nez v renderu
        $this->getAllChitsByObjectId("unit", $this->chits, $this->onlyUnlocked, $this->context->getService("unitAccountService"));
        $this->getAllChitsByObjectId("event", $this->chits, $this->onlyUnlocked, $eventService);
        $this->getAllChitsByObjectId("camp", $this->chits, $this->onlyUnlocked, $campService);
    }

    public function renderDefault($year = NULL) : void
    {
        $this->template->types = [
            "event" => "Výpravy",
            "camp" => "Tábory",
            "unit" => "Jednotky"
        ];
        $this->template->chitsArr = $this->chits;
        $this->template->info = $this->info;
    }

    private function getAllChitsByObjectId($objectType, &$chits, $onlyUnlocked, $service) : void
    {
        if (in_array($objectType, ["event", "camp"])) {//filtrování akcí spojených pouze s danou jednotkou
            $ids = [];
            foreach ($this->info[$objectType] as $k => $e) {

                if (array_key_exists($e['ID_Unit'], $this->info['unit'])) {
                    $ids[] = $k;
                }
            }
        } else {
            $ids = array_keys($this->info[$objectType]);
        }
        foreach ($ids as $oid) {
            $arr = $service->chits->getAll($oid, $onlyUnlocked);
            if (empty($arr)) {
                continue;
            }
            $chits[$objectType][$oid] = $service->chits->getAll($oid, $onlyUnlocked);
        }
    }

    public function handleLockCashbook(int $eventId, string $type) : void
    {
        if (!in_array($type, ["event", "camp", "unit"], TRUE) || !array_key_exists($eventId, $this->info[$type])) {
            $this->flashMessage("Neplatný přístup!", "danger");
        } else {
            $cashbookId = $this->cashbookService->getSkautisIdFromCashbookId($eventId, ObjectType::get($type));
            $this->commandBus->handle(new LockCashbook($cashbookId, $this->user->getId()));
        }

        if ($this->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect("default");
        }
    }

    /**
     * @param string $type type of object - camp, unit, event
     */
    public function handleLock(int $cashbookId, int $chitId, string $type, string $act = "lock") : void
    {
        $allowedTypes = ["event", "camp", "unit"];

        if ( ! in_array($type, $allowedTypes, TRUE) || ! $this->accessChitControl($cashbookId, $type)) {
            $this->flashMessage("Neplatný přístup!", "danger");
            $this->redraw();
            return;
        }

        if ($act === 'lock') {
            $this->commandBus->handle(new LockChit($cashbookId, $chitId, $this->user->getId()));
        } elseif($act === 'unlock') {
            $this->commandBus->handle(new UnlockChit($cashbookId, $chitId));
        }

        $this->redraw();
    }

    protected function createComponentBudgetCategoryForm(): BaseForm
    {
        $categories = $this->budgetService->getCategoriesLeaf($this->aid);
        $form = new BaseForm();
        foreach ($this->chits as $chType) {
            foreach ($chType as $chGrp) {
                foreach ($chGrp as $ch) {
                    $form->addSelect("selectBudget_in_" . $ch->id, NULL, $categories['in'])
                        ->setPrompt("")
                        ->setAttribute("class", "form-control input-sm")
                        ->setDefaultValue($ch->budgetCategoryIn)
                        ->getControlPrototype()->setAttribute("class", "input-medium");
                    $form->addSelect("selectBudget_out_" . $ch->id, NULL, $categories['out'])
                        ->setPrompt("")
                        ->setAttribute("class", "form-control input-sm")
                        ->setDefaultValue($ch->budgetCategoryOut)
                        ->getControlPrototype();
                }
            }
        }

        $form->onSubmit[] = function(BaseForm $form) : void {
            $this->budgetCategoryFormSubmitted($form);
        };

        $form->addSubmit("send", "Uložit kategorie")
            ->setAttribute("class", "btn btn-primary");
        return $form;
    }

    private function budgetCategoryFormSubmitted(BaseForm $form): void
    {
        $v = $form->values;
        foreach ($this->chits as $chType) {
            foreach ($chType as $chGrp) {
                foreach ($chGrp as $ch) {
                    $this->context->getService("eventService")->chits->setBudgetCategories($ch->id, $v['selectBudget_in_' . $ch->id], $v['selectBudget_out_' . $ch->id]); //zde může být libovolný EntityService
                }
            }
        }

        $this->flashMessage("Kategorie byly upraveny.");
        $this->redirect("this");
    }

    private function redraw(): void
    {
        if ($this->isAjax()) {
            $this->redrawControl('flash');
            $this->redrawControl('tableChits');
            $this->redrawControl();
        } else {
            $this->redirect("default");
        }
    }

    private function accessChitControl(int $cashbookId, string $type): bool
    {
        // in this presenter 'event' is used instead of 'general' for some reason
        $cashbookType = ObjectType::get($type === 'event' ? ObjectType::EVENT : $type);
        $skautisId = $this->cashbookService->getSkautisIdFromCashbookId($cashbookId, $cashbookType);

        return array_key_exists($skautisId, $this->info[$type]);
    }

}
