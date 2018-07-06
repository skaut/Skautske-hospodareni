<?php

namespace App\AccountancyModule\UnitAccountModule;

use App\Forms\BaseForm;
use NasExt\Forms\DependentData;
use Nette\Application\UI\Form;

class BudgetPresenter extends BasePresenter
{

    /** @var \Model\BudgetService */
    protected $budgetService;

    public function __construct(\Model\BudgetService $budgetService)
    {
        parent::__construct();
        $this->budgetService = $budgetService;
    }

    public function renderDefault($year = NULL) : void
    {
        $this->template->categories = $this->budgetService->getCategories($this->aid);
        $this->template->unitPairs = $this->unitService->getReadUnits($this->user);
    }

    public function getParentCategories(array $values) : DependentData
    {
        $items = $this->budgetService->getCategoriesRoot((int)$this->aid, $values['type']);

        return new DependentData(
            ['0' => 'Žádná'] + $items
        );
    }

    protected function createComponentAddCategoryForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addText("label", "Název")
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Vyplňte název kategorie");
        $form->addSelect("type", "Typ", ["in" => "Příjmy", "out" => "Výdaje"])
            ->setDefaultValue('in')
            ->setAttribute("class", "form-control")
            ->addRule(Form::FILLED, "Vyberte typ")
            ->setHtmlId("form-select-type");
        $form->addDependentSelectBox('parentId', 'Nadřazená kategorie', $form['type'])
            ->setDependentCallback([$this, 'getParentCategories'])
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

        $form->addSubmit("submit", "Založit kategorii")
            ->setAttribute("class", "btn btn-primary");

        $form->onSubmit[] = function(Form $form) : void {
            $this->addCategoryFormSubmitted($form);
        };

        return $form;
    }

    private function addCategoryFormSubmitted(Form $form) : void
    {
        if($form["submit"]->isSubmittedBy()) {
            $v = $form->values;
            $this->budgetService->addCategory($v->oid, $v->label, $v->type, $v->parentId == 0 ? NULL : $v->parentId, $v->value, $v->year);
            $this->flashMessage("Kategorie byla přidána.");
            $this->redirect("default");
        }
    }

}
