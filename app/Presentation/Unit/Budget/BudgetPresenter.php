<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Budget;

use App\Model\Budget\BudgetService;
use App\Presentation\Unit\UnitBasePresenter;
use Component\Forms\BaseForm;
use LogicException;
use NasExt\Forms\DependentData;
use Nette\Application\UI\Form;

use function date;

class BudgetPresenter extends UnitBasePresenter
{
    public function __construct(protected BudgetService $budgetService)
    {
        parent::__construct();
    }

    public function renderDefault(?int $year = null): void
    {
        $this->template->setParameters([
            'categories' => $this->budgetService->getCategories($this->unitId->toInt()),
            'unitPairs' => $this->unitService->getReadUnits($this->user),
            'year' => $year,
        ]);
    }

    public function renderAdd(?int $year = null): void
    {
        $form = $this['addCategoryForm'];
        if (! $form instanceof BaseForm) {
            throw new LogicException('Assertion failed.');
        }
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
        $this->redirect('default', ['unitId' => $this->unitId->toInt(), 'year' => (int) $v->year]);
    }
}
