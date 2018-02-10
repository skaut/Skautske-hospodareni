<?php

namespace App\AccountancyModule\CampModule;

use App\Forms\BaseForm;
use Cake\Chronos\Date;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Event\Commands\Camp\ActivateAutocomputedCashbook;

class CashbookPresenter extends BasePresenter
{

    use \CashbookTrait;

    protected function startup() : void
    {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "danger");
            $this->redirect("Default:");
        }
        $this->entityService = $this->eventService;
        $this->template->isEditable = $this->isEditable = ($this->isEditable || $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCostBeforeEnd"));
    }

    public function renderDefault(int $aid, $pid = NULL, $dp = FALSE) : void
    {
        if ($pid !== NULL) {
            $this->editChit($pid);
        }

        $this->template->isInMinus = $this->eventService->chits->eventIsInMinus($this->getCurrentUnitId());

        $this->template->list = $this->eventService->chits->getAll($aid);
        $this->template->missingCategories = FALSE;
        $this->template->linkImportHPD = "#importHpd";

        $this->fillTemplateVariables();

        if (!$this->event->IsRealTotalCostAutoComputed) { //nabízí možnost aktivovat dopočítávání, pokud již není aktivní a je dostupná
            $this->template->missingCategories = TRUE; //boolean - nastavuje upozornění na chybějící dopočítávání kategorií
        }
        if ($this->isAjax()) {
            $this->redrawControl("contentSnip");
        }
    }

    public function handleActivateAutocomputedCashbook(int $aid) : void
    {
        try {
            $this->commandBus->handle(new ActivateAutocomputedCashbook($aid));
            $this->flashMessage("Byl aktivován automatický výpočet příjmů a výdajů v rozpočtu.");
        } catch (\Skautis\Wsdl\PermissionException $e) {
            $this->flashMessage("Dopočítávání se nepodařilo aktivovat. Pro aktivaci musí být tábor alespoň ve stavu schváleno střediskem.", "danger");
        }

        $this->redirect("this");
    }

    protected function createComponentFormImportHpd(): BaseForm
    {
        $form = new BaseForm();
        $form->addRadioList("cat", "Kategorie:", ["child" => "Od dětí a roverů", "adult" => "Od dospělých"])
            ->addRule($form::FILLED, "Musíte vyplnit kategorii.")
            ->setDefaultValue("child");
        $form->addRadioList("isAccount", "Placeno:", ["N" => "Hotově", "Y" => "Přes účet"])
            ->addRule($form::FILLED, "Musíte vyplnit způsob platby.")
            ->setDefaultValue("N");

        $form->addSubmit('send', 'Importovat')
            ->setAttribute("class", "btn btn-primary");

        $form->onSuccess[] = function(BaseForm $form) : void {
            $this->formImportHpdSubmitted($form);
        };

        $form->setDefaults(['category' => 'un']);

        return $form;
    }

    private function formImportHpdSubmitted(BaseForm $form) : void
    {
        $this->editableOnly();
        $values = $form->getValues();

        $aid = $this->getCurrentUnitId();
        $cashbookId = $this->eventService->chits->getCashbookIdFromSkautisId($aid);
        $date = $this->eventService->event->get($aid)->StartDate;
        $amount = $this->eventService->participants->getCampTotalPayment($aid, $values->cat, $values->isAccount);
        $categoryId = $this->eventService->chits->getParticipantIncomeCategory($aid, $values->cat);

        if ($amount === 0.0) {
            $this->flashMessage('Nemáte žádné příjmy od účastníků, které by bylo možné importovat.', 'warning');
            $this->redirect('default', ['aid' => $aid]);
        }

        $this->commandBus->handle(
            new AddChitToCashbook(
                $cashbookId,
                NULL,
                new Date($date),
                NULL,
                new Amount((string) $amount),
                "úč. příspěvky " . ($values->isAccount == "Y" ? "- účet" : "- hotovost"),
                $categoryId
            )
        );

        $this->flashMessage("HPD byl importován");
        $this->redirect("default", ["aid" => $aid]);
    }

}
