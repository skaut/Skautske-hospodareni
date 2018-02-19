<?php

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Components\ChitForm;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use App\AccountancyModule\Factories\IChitFormFactory;

trait CashbookTrait
{

    /** @var \Model\EventEntity */
    protected $entityService;

    /** @var \Nette\Utils\ArrayHash */
    protected $event;

    /** @var ICashbookControlFactory */
    private $cashbookFactory;

    /** @var IChitFormFactory */
    private $formFactory;

    public function injectConstruct(ICashbookControlFactory $cashbookFactory, IChitFormFactory $formFactory): void
    {
        $this->cashbookFactory = $cashbookFactory;
        $this->formFactory = $formFactory;
    }

    protected function createComponentChitForm(string $name): ChitForm
    {
        $control = $this->formFactory->create($this->getCashbookId(), $this->isEditable);

        $this->addComponent($control, $name); // necessary for JSelect

        return $control;
    }

    protected function createComponentCashbook(): CashbookControl
    {
        $cashbookId = $this->entityService->chits->getCashbookIdFromSkautisId($this->aid);

        return $this->cashbookFactory->create($cashbookId, $this->isEditable);
    }

    public function fillTemplateVariables(): void
    {
        $this->template->object = $this->event;
        $this->template->cashbookId = $this->getCashbookId();
    }

    private function getCashbookId(): int
    {
        return $this->entityService->chits->getCashbookIdFromSkautisId($this->aid);
    }
}
