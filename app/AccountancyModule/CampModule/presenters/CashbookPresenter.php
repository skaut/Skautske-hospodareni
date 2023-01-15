<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\EventModule\Components\MissingAutocomputedCategoryControl;
use App\AccountancyModule\EventModule\Factories\IMissingAutocomputedCategoryControlFactory;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use App\Forms\BaseForm;
use Model\Auth\Resources\Camp;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\MissingCategory;
use Model\Cashbook\ParticipantType;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CampParticipantCategoryIdQuery;
use Model\Cashbook\ReadModel\Queries\CampParticipantIncomeQuery;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\Cashbook\ReadModel\Queries\FinalRealBalanceQuery;
use Model\DTO\Cashbook\ChitItem;
use Model\Event\Commands\Camp\ActivateAutocomputedCashbook;
use Model\Event\SkautisCampId;
use Model\Participant\ZeroParticipantIncome;
use Money\Money;
use Skautis\Wsdl\PermissionException;

use function assert;
use function count;

class CashbookPresenter extends BasePresenter
{
    private bool $isRealTotalCostAutoComputed;

    public function __construct(
        private ICashbookControlFactory $cashbookFactory,
        private IMissingAutocomputedCategoryControlFactory $categoryAutocomputedFactory,
    ) {
        parent::__construct();
    }

    protected function startup(): void
    {
        parent::startup();
        $this->isEditable                  = $this->isEditable || $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $this->getCampId());
        $this->isRealTotalCostAutoComputed = ! $this->event->isRealTotalCostAutoComputed();
    }

    public function renderDefault(int $aid): void
    {
        $finalBalance      = $this->queryBus->handle(new FinalCashBalanceQuery($this->getCashbookId()));
        $missingCategories = $this->isRealTotalCostAutoComputed;
        try {
            $finalRealBalance = $this->queryBus->handle(new FinalRealBalanceQuery($this->getCashbookId()));
            assert($finalRealBalance instanceof Money);
        } catch (MissingCategory) {
            $finalRealBalance  = null;
            $missingCategories = true;
        }

        assert($finalBalance instanceof Money);

        $this->template->setParameters([
            'isCashbookEmpty' => $this->isCashbookEmpty(),
            'cashbookId' => $this->getCashbookId()->toString(),
            'isInMinus' => $finalBalance->isNegative(),
            'isEditable' => $this->isEditable,
            'missingCategories' => $missingCategories,
            'finalRealBalance' => $finalRealBalance,
        ]);
    }

    public function handleActivateAutocomputedCashbook(int $aid): void
    {
        try {
            $this->commandBus->handle(new ActivateAutocomputedCashbook(new SkautisCampId($this->getCampId())));
            $this->flashMessage('Byl aktivován automatický výpočet příjmů a výdajů v rozpočtu.');
        } catch (PermissionException) {
            $this->flashMessage('Dopočítávání se nepodařilo aktivovat. Pro aktivaci musí být tábor alespoň ve stavu schváleno střediskem.', 'danger');
        }

        $this->redirect('this');
    }

    protected function createComponentCashbook(): CashbookControl
    {
        return $this->cashbookFactory->create(
            $this->getCashbookId(),
            $this->isEditable && ! $this->isRealTotalCostAutoComputed,
            $this->getCurrentUnitId(),
        );
    }

    protected function createComponentFormImportHpd(): BaseForm
    {
        $form = new BaseForm();
        $form->addRadioList('cat', 'Kategorie:', ['child' => 'Od dětí a roverů', 'adult' => 'Od dospělých'])
            ->addRule($form::FILLED, 'Musíte vyplnit kategorii.')
            ->setDefaultValue('child');
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

        try {
            $amount = $this->queryBus->handle(new CampParticipantIncomeQuery(
                new SkautisCampId($this->getCampId()),
                $values->cat === 'adult',
                $values->isAccount === 'Y',
            ));
            assert($amount instanceof Amount);
        } catch (ZeroParticipantIncome) {
            $this->flashMessage('Nemáte žádné příjmy od účastníků, které by bylo možné importovat.', 'warning');
            $this->redirect('default', ['aid' => $this->getCampId()]);
        }

        $purpose = 'úč. příspěvky ' . ($values->isAccount === 'Y' ? '- účet' : '- hotovost');
        $body    = new ChitBody(null, $this->event->getStartDate(), null);

        $categoryId    = $this->queryBus->handle(
            new CampParticipantCategoryIdQuery(new SkautisCampId($this->getCampId()), ParticipantType::get($values->cat === 'adult' ? ParticipantType::ADULT : ParticipantType::CHILD)),
        );
        $categoriesDto = $this->queryBus->handle(new CategoryListQuery($this->getCashbookId()));

        $items = [new ChitItem($amount, $categoriesDto[$categoryId], $purpose)];
        $this->commandBus->handle(new AddChitToCashbook($this->getCashbookId(), $body, $values->isAccount === 'Y' ? PaymentMethod::BANK() : PaymentMethod::CASH(), $items));

        $this->flashMessage('HPD byl importován');

        $this->redirect('default', $this->getCampId());
    }

    private function getCashbookId(): CashbookId
    {
        return $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($this->getCampId())));
    }

    private function isCashbookEmpty(): bool
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->getCashbookId()));

        return count($chits) === 0;
    }

    protected function createComponentCategoryAutocomputedControl(): MissingAutocomputedCategoryControl
    {
        return $this->categoryAutocomputedFactory->create(new SkautisCampId($this->aid));
    }
}
