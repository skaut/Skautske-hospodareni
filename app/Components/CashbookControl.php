<?php

declare(strict_types=1);

namespace App\Components;

use App\Components\Cashbook\ChitListControl;
use App\Components\Factories\Cashbook\IChitListControlFactory;
use App\Components\Factories\Cashbook\INoteFormFactory;
use App\Components\Factories\IChitFormFactory;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Common\UnitId;

class CashbookControl extends BaseControl
{
    /** @var bool @persistent */
    public bool $displayChitForm = false;

    private INoteFormFactory $noteFormFactory;

    public function __construct(
        private CashbookId $cashbookId,
        private bool $isEditable,
        private UnitId $unitId,
        private IChitFormFactory $formFactory,
        private IChitListControlFactory $chitListFactory,
        INoteFormFactory $noteFactory,
    ) {
        $this->noteFormFactory = $noteFactory;
    }

    public function render(): void
    {
        $this->template->setParameters([
            'isEditable' => $this->isEditable,
        ]);

        $this->template->setFile(__DIR__.'/templates/CashbookControl.latte');
        $this->template->render();
    }

    protected function createComponentChitForm(): ChitForm
    {
        $control = $this->formFactory->create($this->cashbookId, $this->isEditable, $this->unitId);
        $control->setDisplayChitForm($this->displayChitForm);

        return $control;
    }

    protected function createComponentChitListCash(): ChitListControl
    {
        return $this->createChitList(PaymentMethod::CASH());
    }

    protected function createComponentChitListBank(): ChitListControl
    {
        return $this->createChitList(PaymentMethod::BANK());
    }

    protected function createComponentNoteForm(): NoteForm
    {
        return $this->noteFormFactory->create($this->cashbookId, $this->isEditable);
    }

    private function createChitList(PaymentMethod $paymentMethod): ChitListControl
    {
        $control = $this->chitListFactory->create($this->cashbookId, $this->isEditable, $paymentMethod);

        $this->displayChitForm = true;
        $this['chitForm']->setDisplayChitForm(true);

        $control->onEditButtonClicked[] = function (int $chitId): void {
            $form = $this['chitForm'];
            $form->editChit($chitId);

            $form->redrawControl();
        };

        return $control;
    }
}
