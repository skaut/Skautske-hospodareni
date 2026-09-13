<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Budget;

use App\Model\Budget\BudgetService;
use App\Presentation\Unit\UnitBasePresenter;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SelectBox;
use Nette\Utils\Json;

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

    /** @return array<int, string> */
    private function rootCategoryPairs(string $type): array
    {
        return $this->budgetService->getCategoriesRoot($this->unitId->toInt(), $type);
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

        // Nadřazené kategorie závisí na typu. Nabídku přepíná na klientu dependentSelect.ts podle
        // data-items; server ji přenastaví v onAnchor (validace + fungování bez JS).
        $parentId = $form->addSelect('parentId', 'Nadřazená kategorie')
            ->setPrompt('Žádná')
            ->setHtmlAttribute('data-depends', $type->getHtmlName())
            ->setHtmlAttribute('data-items', Json::encode([
                'in' => $this->rootCategoryPairs('in'),
                'out' => $this->rootCategoryPairs('out'),
            ]));
        $parentId->addCondition(Form::FILLED)
            ->toggle('form-category-value');

        $form->onAnchor[] = function () use ($form, $parentId): void {
            $typeControl = $form->getComponent('type');
            $selectedType = $typeControl instanceof SelectBox ? (string) $typeControl->getValue() : 'in';
            $parentId->setItems($this->rootCategoryPairs($selectedType === '' ? 'in' : $selectedType));
        };

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
