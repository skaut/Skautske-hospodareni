<?php

namespace App\AccountancyModule\UnitAccountModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class ChitPresenter extends BasePresenter
{

    public $chits;
    public $info;

    /** @persistent */
    public $onlyUnlocked = 1;

    /**
     *
     * @var \Model\BudgetService
     */
    protected $budgetService;

    public function __construct(\Model\BudgetService $bs)
    {
        parent::__construct();
        $this->budgetService = $bs;
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
        foreach ($this->user->getIdentity()->access['edit'] as $ik => $iu) {
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

    public function handleLockEvent($sid, $type) : void
    {
        if (!in_array($type, ["event", "camp", "unit"]) || !array_key_exists($sid, $this->info[$type])) {
            $this->flashMessage("Neplatný přístup!", "danger");
        } else {
            $service = $this->context->getService(($type == "unit" ? "unitAccount" : $type) . "Service");
            $service->chits->lockEvent($service->chits->getLocalId($sid), $this->user->id);
        }

        if ($this->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect("default");
        }
    }

    /**
     *
     * @param int $oid - id of object
     * @param int $id - id of chit
     * @param string $type type of object - camp, unit, event
     * @param string $act
     */
    public function handleLock($oid, $id, $type, $act = "lock") : void
    {
        if (!in_array($type, ["event", "camp", "unit"])) {
            $this->flashMessage("Neplatný přístup!", "danger");
        } else {
            $service = $this->context->getService(($type == "unit" ? "unitAccount" : $type) . "Service");
            $chit = $service->chits->get($id);
            if (!$this->accessChitControl($chit, $service, $type)) {
                $this->flashMessage("Neplatný přístup!", "danger");
            } elseif (in_array($act, ["lock", "unlock"])) {
                $service->chits->{$act}($oid, $id, $this->user->id);
            }
        }

        if ($this->isAjax()) {
            $this->redrawControl('flash');
            $this->redrawControl('tableChits');
            $this->redrawControl();
        } else {
            $this->redirect("default");
        }
    }

    protected function createComponentBudgetCategoryForm($name) : Form
    {
        $categories = $this->budgetService->getCategoriesLeaf($this->aid);
        $form = $this->prepareForm($this, $name);
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

        $form->onSubmit[] = function(Form $form) : void {
            $this->budgetCategoryFormSubmitted($form);
        };

        $form->addSubmit("send", "Uložit kategorie")
            ->setAttribute("class", "btn btn-primary");
        return $form;
    }

    private function budgetCategoryFormSubmitted(Form $form) : void
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

    private function accessChitControl($chit, $service, $type)
    {
        //dump($type);dump($chit);die();
        return array_key_exists($service->chits->getSkautisId($chit->eventId), $this->info[$type]);
        //return array_key_exists($chit->id, $this->chits[$type][$this->context->{$type . "Service"}->chits->getSkautisId($chit->eventId)]);
    }

}
