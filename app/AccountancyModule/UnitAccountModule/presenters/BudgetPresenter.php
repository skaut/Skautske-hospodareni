<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule;

use App\Forms\BaseForm;
use Model\BudgetService;
use NasExt\Forms\DependentData;
use Nette\Application\UI\Form;

use function assert;
use function date;

class BudgetPresenter extends BasePresenter
{
    public function __construct(protected BudgetService $budgetService)
    {
        parent::__construct();
    }

    public function renderDefault(int|null $year = null): void
    {
        $this->template->setParameters([
            'categories' => $this->budgetService->getCategories($this->unitId->toInt()),
            'unitPairs'  => $this->unitService->getReadUnits($this->user),
            'year'       => $year,
        ]);
    }

    public function renderAdd(int|null $year = null): void
    {
        $form = $this['addCategoryForm'];
        assert($form instanceof BaseForm);
        $form->setDefaults([
            'year' => $year ?? date('Y'),
        ]);
    }

    /** @param mixed[] $values */
    public function getParentCategories(array $values): DependentData
    {
        $items = $this->budgetService->getCategoriesRoot($this->unitId->toInt(), $values['type']);

        return new DependentData($items);
    }

    protected function createComponentAddCategoryForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addText('label', 'Název')
            ->addRule(Form::FILLED, 'Vyplňte název kategorie');

        $type = $form->addSelect('type', 'Typ', ['in' => 'Příjmy', 'out' => 'Výdaje'])
            ->setDefaultValue('in')
            ->addRule(Form::FILLED, 'Vyberte typ')
            ->setHtmlId('form-select-type');

        $form->addDependentSelectBox('parentId', 'Nadřazená kategorie', $type)
            ->setDependentCallback([$this, 'getParentCategories'])
            ->setPrompt('Žádná')
            ->addCondition(Form::FILLED)
            ->toggle('form-category-value');

        $form->addText('value', 'Částka')
            ->setOption('id', 'form-category-value');

        $form->addText('year', 'Rok')
            ->addRule(Form::FILLED, 'Vyplňte rok');

        $form->addHidden('oid', $this->unitId->toInt());

        $form->addSubmit('submit', 'Založit kategorii')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (Form $form): void {
            $this->addCategoryFormSubmitted($form);
        };

        return $form;
    }

    private function addCategoryFormSubmitted(Form $form): void
    {
        if ($form->isSubmitted() !== $form['submit']) {
            return;
        }

        $v = $form->values;
        $this->budgetService->addCategory((int) $v->oid, $v->label, $v->type, $v->parentId === 0 ? null : $v->parentId, $v->value, (int) $v->year);
        $this->flashMessage('Kategorie byla přidána.', 'success');
        $this->redirect('default');
    }
}
