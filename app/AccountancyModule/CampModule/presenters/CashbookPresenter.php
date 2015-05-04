<?php

namespace App\AccountancyModule\CampModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class CashbookPresenter extends BasePresenter {

    use \CashbookTrait;

    /**
     * @var \Model\MemberService
     */
    protected $memberService;

    /**
     * @var \Model\ExportService
     */
    protected $exportService;

    /**
     * @var \Model\ExcelService
     */
    protected $excelService;

    public function __construct(\Model\MemberService $member, \Model\ExportService $es, \Model\ExcelService $exs) {
        parent::__construct();
        $this->memberService = $member;
        $this->exportService = $es;
        $this->excelService = $exs;
    }

    function startup() {
        parent::startup();
        if (!$this->aid) {
            $this->flashMessage("Musíš vybrat akci", "danger");
            $this->redirect("Default:");
        }
        $this->entityService = $this->campService;
        $this->template->isEditable = $this->isEditable = ($this->isEditable || $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCostBeforeEnd"));
//        $this->template->isEditable = $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCost");
    }

    public function renderDefault($aid, $pid = NULL, $dp = FALSE) {
        if ($pid !== NULL) {
            $this->isChitEditable($pid, $this->entityService);
            $form = $this['cashbookForm'];
            $chit = $this->entityService->chits->get($pid);
            $form['category']->setItems($this->entityService->chits->getCategoriesPairs($chit->ctype, $this->aid));
            $form->setDefaults(array(
                "pid" => $pid,
                "date" => $chit->date->format("j. n. Y"),
                "recipient" => $chit->recipient,
                "purpose" => $chit->purpose,
                "price" => $chit->priceText,
                "type" => $chit->ctype,
                "category" => $chit->category,
            ));
        }

        $this->template->isInMinus = $this->campService->chits->eventIsInMinus($this->aid);
        $this->template->autoCompleter = $dp ? array() : array_values($this->memberService->getCombobox(FALSE, 15));
        $this->template->list = $this->campService->chits->getAll($aid);
        $this->template->missingCategories = false;
        $this->template->linkImportHPD = "#importHpd";
        $this->template->object = $this->event;
//        dump($this->camp);
        if (!$this->event->IsRealTotalCostAutoComputed) { //nabízí možnost aktivovat dopočítávání, pokud již není aktivní a je dostupná
            //$this->template->isAllowedUpdateRealTotalCost = $this->isAllowed("EV_EventCamp_UPDATE_RealTotalCost");
            $this->template->missingCategories = true; //boolean - nastavuje upozornění na chybějící dopočítávání kategorií
        }
        if ($this->isAjax()) {
            $this->invalidateControl("contentSnip");
        }
    }

    public function handleActivateAutocomputedCashbook($aid) {
        try {
            $this->campService->event->activateAutocomputedCashbook($aid);
            $this->flashMessage("Byl aktivován automatický výpočet příjmů a výdajů v rozpočtu.");
        } catch (\Skautis\Wsdl\PermissionException $e){
            $this->flashMessage("Dopočítávání se nepodařilo aktivovat. Pro aktivaci musí být tábor alespoň ve stavu schváleno střediskem.", "danger");
        }
        $this->redirect("this");
    }

    /**
     * formulář na výběr příjmů k importu
     * @param type $name
     * @return \Nette\Application\UI\Form
     */
    function createComponentFormImportHpd($name) {
        $form = $this->prepareForm($this, $name);
        $form->addRadioList("cat", "Kategorie:", array("child" => "Od dětí a roverů", "adult" => "Od dospělých"))
                ->addRule(Form::FILLED, "Musíte vyplnit kategorii.")
                ->setDefaultValue("child");
        $form->addRadioList("isAccount", "Placeno:", array("N" => "Hotově", "Y" => "Přes účet"))
                ->addRule(Form::FILLED, "Musíte vyplnit způsob platby.")
                ->setDefaultValue("N");
        $form->addHidden("aid", $this->aid);

        $form->addSubmit('send', 'Importovat')
                ->setAttribute("class", "btn btn-primary");
        $form->onSuccess[] = array($this, $name . 'Submitted');
        $form->setDefaults(array('category' => 'un'));
        return $form;
    }

    function formImportHpdSubmitted(Form $form) {
        $this->editableOnly();
        $values = $form->getValues();
        $data = array("date" => $this->campService->event->get($values->aid)->StartDate,
            "recipient" => "",
            "purpose" => "úč. příspěvky " . ($values->isAccount == "Y" ? "- účet" : "- hotovost"),
            "price" => $this->campService->participants->getCampTotalPayment($values->aid, $values->cat, $values->isAccount),
            "category" => $this->campService->chits->getParticipantIncomeCategory($values->aid, $values->cat));

        if ($this->campService->chits->add($values->aid, $data)) {
            $this->flashMessage("HPD byl importován");
        } else {
            $this->flashMessage("HPD se nepodařilo importovat", "danger");
        }
        $this->redirect("default", array("aid" => $values->aid));
    }

}
