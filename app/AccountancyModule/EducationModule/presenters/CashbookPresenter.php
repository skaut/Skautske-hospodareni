<?php

declare(strict_types=1);

namespace App\AccountancyModule\EducationModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use App\Forms\BaseForm;
use Model\Auth\Resources\Education;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\MissingCategory;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\EducationCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\EducationParticipantCategoryIdQuery;
use Model\Cashbook\ReadModel\Queries\EducationParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\DTO\Cashbook\ChitItem;
use Model\Event\SkautisEducationId;
use Money\Money;

use function assert;
use function count;

class CashbookPresenter extends BasePresenter
{
    public function __construct(
        private ICashbookControlFactory $cashbookFactory,
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();

        $this->isEditable = $this->isEditable || $this->authorizator->isAllowed(Education::UPDATE_REAL_BUDGET_SPENDING, $this->aid);
    }

    public function renderDefault(int $aid): void
    {
        if (! $this->authorizator->isAllowed(Education::ACCESS_BUDGET, $this->aid)) {
            $this->flashMessage('Nemáte právo prohlížet platby akce', 'danger');
            $this->redirect('Education:');
        }

        $finalBalance = $this->queryBus->handle(new FinalCashBalanceQuery($this->getCashbookId()));
        try {
            $finalRealBalance = $this->queryBus->handle(new FinalRealBalanceQuery($this->getCashbookId()));
            assert($finalRealBalance instanceof Money);
        } catch (MissingCategory) {
            $finalRealBalance = null;
        }

        assert($finalBalance instanceof Money);

        $this->template->setParameters([
            'isCashbookEmpty' => $this->isCashbookEmpty(),
            'cashbookId' => $this->getCashbookId()->toString(),
            'isInMinus' => $finalBalance->isNegative(),
            'isEditable' => $this->isEditable,
            'finalRealBalance' => $finalRealBalance,
        ]);
    }

    protected function createComponentCashbook(): CashbookControl
    {
        return $this->cashbookFactory->create(
            $this->getCashbookId(),
            $this->isEditable,
            $this->getCurrentUnitId(),
        );
    }

    protected function createComponentFormImportHpd(): BaseForm
    {
        $form = new BaseForm();
        $form->addRadioList('isAccount', 'Placeno:', ['N' => 'Hotově', 'Y' => 'Přes účet'])
            ->addRule($form::FILLED, 'Musíte vyplnit způsob platby.')
            ->setDefaultValue('N');

        $form->addSubmit('send', 'Importovat')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formImportHpdSubmitted($form);
        };

        $form->setDefaults(['category' => 'un']);

        return $form;
    }

    private function formImportHpdSubmitted(BaseForm $form): void
    {
        $this->editableOnly();
        $values = $form->getValues();

        $amount = $this->queryBus->handle(new EducationParticipantIncomeQuery(new SkautisEducationId($this->aid)));

        if ($amount === 0.0) {
            $this->flashMessage('Nemáte žádné příjmy od účastníků, které by bylo možné importovat.', 'warning');
            $this->redirect('default', ['aid' => $this->aid]);
        }

        $purpose = 'úč. příspěvky ' . ($values->isAccount === 'Y' ? '- účet' : '- hotovost');
        $body    = new ChitBody(null, $this->event->getStartDate(), null);

        $categoryId    = $this->queryBus->handle(
            new EducationParticipantCategoryIdQuery(new SkautisEducationId($this->aid)),
        );
        $categoriesDto = $this->queryBus->handle(new CategoryListQuery($this->getCashbookId()));

        $items = [new ChitItem(Amount::fromFloat($amount), $categoriesDto[$categoryId], $purpose)];
        $this->commandBus->handle(new AddChitToCashbook($this->getCashbookId(), $body, $values->isAccount === 'Y' ? PaymentMethod::BANK() : PaymentMethod::CASH(), $items));

        $this->flashMessage('HPD byl importován');

        $this->redirect('default', $this->aid);
    }

    private function getCashbookId(): CashbookId
    {
        return $this->queryBus->handle(new EducationCashbookIdQuery(new SkautisEducationId($this->aid)));
    }

    private function isCashbookEmpty(): bool
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->getCashbookId()));

        return count($chits) === 0;
    }
}
