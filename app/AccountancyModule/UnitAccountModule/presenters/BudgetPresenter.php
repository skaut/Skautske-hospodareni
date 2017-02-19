<?php

namespace App\AccountancyModule\UnitAccountModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BudgetPresenter extends BasePresenter
{

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

    public function renderDefault($year = NULL)
    {
        $this->template->categories = $this->budgetService->getCategories($this->aid);
        $this->template->categoriesSummary = $this->context->getService("unitAccountService")->chits->getBudgetCategoriesSummary($this->budgetService->getCategoriesLeaf($this->aid));
        $this->template->sum = $this->template->sumReality = 0; //je potreba kvuli sablone, kde se pouzije jako globalni promena
        $this->template->unitPairs = $this->unitService->getReadUnits($this->user);
    }

    public function getParentCategories($form, $dependentSelectBoxName)
    {
        return ["0" => "Žádná"] + $this->budgetService->getCategoriesRoot($this->aid, $form["type"]->getValue());
    }

    protected function createComponentAddCategoryForm($name)
    {
        $form = $this->prepareForm($this, $name); // required for full running
        $form->addText("label", "Název")
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Vyplňte název kategorie");
        $form->addSelect("type", "Typ", ["in" => "Příjmy", "out" => "Výdaje"])
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Vyberte typ")
            ->setHtmlId("form-select-type");
        $form->addJSelect("parentId", "Nadřazená kategorie", $form["type"], [$this, "getParentCategories"])
            ->setAttribute("class", "form-control")
            ->setHtmlId("form-select-parentId");
        $form->addText("value", "Částka")
            ->setAttribute("class", "form-control")
            ->setHtmlId("form-category-value");
        $form->addText("year", "Rok")
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Vyplňte rok")
            ->setDefaultValue(date("Y"));
        $form->addHidden('oid', $this->aid);
        $form->onSubmit[] = [$this, $name . 'Submitted'];
        $form->addSubmit("submit", "Založit kategorii")
            ->setAttribute("class", "btn btn-primary");
        return $form;
    }

    public function addCategoryFormSubmitted(Form $form)
    {
        if ($form["submit"]->isSubmittedBy()) {
            $v = $form->values;
            $this->budgetService->addCategory($v->oid, $v->label, $v->type, $v->parentId == 0 ? NULL : $v->parentId, $v->value, $v->year);
            $this->flashMessage("Kategorie byla přidána.");
            $this->redirect("default");
        }
    }

}
