<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\AccountancyModule\Components\Cashbook\ChitListControl;
use App\AccountancyModule\Factories\Cashbook\IChitListControlFactory;
use App\AccountancyModule\Factories\Cashbook\INoteFormFactory;
use App\AccountancyModule\Factories\IChitFormFactory;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;

class CashbookControl extends BaseControl
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var bool */
    private $isEditable;

    /** @var IChitFormFactory */
    private $formFactory;

    /** @var IChitListControlFactory */
    private $chitListFactory;

    /** @var INoteFormFactory */
    private $noteFormFactory;

    public function __construct(CashbookId $cashbookId, bool $isEditable, IChitFormFactory $formFactory, IChitListControlFactory $chitListFactory, INoteFormFactory $noteFactory)
    {
        parent::__construct();
        $this->cashbookId      = $cashbookId;
        $this->isEditable      = $isEditable;
        $this->formFactory     = $formFactory;
        $this->chitListFactory = $chitListFactory;
        $this->noteFormFactory = $noteFactory;
    }

    public function render() : void
    {
        $this->template->setParameters(
            [
            'isEditable' => $this->isEditable,
            ]
        );

        $this->template->setFile(__DIR__ . '/templates/CashbookControl.latte');
        $this->template->render();
    }

    protected function createComponentChitForm() : ChitForm
    {
        return $this->formFactory->create($this->cashbookId, $this->isEditable);
    }

    protected function createComponentChitListCash() : ChitListControl
    {
        return $this->createChitList(PaymentMethod::CASH());
    }

    protected function createComponentChitListBank() : ChitListControl
    {
        return $this->createChitList(PaymentMethod::BANK());
    }

    protected function createComponentNoteForm() : NoteForm
    {
        return $this->noteFormFactory->create($this->cashbookId, $this->isEditable);
    }

    private function createChitList(PaymentMethod $paymentMethod) : ChitListControl
    {
        $control = $this->chitListFactory->create($this->cashbookId, $this->isEditable, $paymentMethod);

        $control->onEditButtonClicked[] = function (int $chitId) : void {
            /** @var ChitForm $form */
            $form = $this['chitForm'];
            $form->editChit($chitId);

            $form->redrawControl();
        };

        return $control;
    }
}
