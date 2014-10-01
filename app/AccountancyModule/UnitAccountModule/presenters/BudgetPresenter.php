<?php

namespace App\AccountancyModule\UnitAccountModule;

use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class BudgetPresenter extends BasePresenter {

    public function renderDefault($year = NULL) {
        $this->template->categories = $this->context->budgetService->getCategories($this->aid);
        $this->template->categoriesSummary = $this->context->unitAccountService->chits->getBudgetCategoriesSummary($this->context->budgetService->getCategoriesLeaf($this->aid));
        $this->template->sum = $this->template->sumReality = 0; //je potreba kvuli sablone, kde se pouzije jako globalni promena
    }

    public function getParentCategories($form, $dependentSelectBoxName) {
        return array("0" => "Žádná") + $this->context->budgetService->getCategoriesRoot($this->aid, $form["type"]->getValue());
    }

    protected function createComponentAddCategoryForm($name) {
        $form = new Form($this, $name); // required for full running
        $form->addText("label", "Název")
                ->addRule(Form::FILLED, "Vyplňte název kategorie");
        $form->addSelect("type", "Typ", array("in" => "Příjmy", "out" => "Výdaje"))
                ->addRule(Form::FILLED, "Vyberte typ")
                ->setHtmlId("form-select-type");
        $form->addJSelect("parentId", "Nadřazená kategorie", $form["type"], array($this, "getParentCategories"))
                ->setHtmlId("form-select-parentId");
        $form->addText("value", "Částka")
                ->setHtmlId("form-category-value");
        $form->addText("year", "Rok")
                ->addRule(Form::FILLED, "Vyplňte rok")
                ->setDefaultValue(date("Y"));
        $form->addHidden('oid', $this->aid);
        $form->onSubmit[] = array($this, $name . 'Submitted');
        $form->addSubmit("submit", "Založit kategorii")
                ->setAttribute("class", "btn btn-primary");
        return $form;
    }

    public function addCategoryFormSubmitted(Form $form) {
        if ($form["submit"]->isSubmittedBy()) {
            $v = $form->values;
            $this->context->budgetService->addCategory($v->oid, $v->label, $v->type, $v->parentId == 0 ? NULL : $v->parentId, $v->value, $v->year);
            $this->flashMessage("Kategorie byla přidána.");
            $this->redirect("default");
        }
    }

}
