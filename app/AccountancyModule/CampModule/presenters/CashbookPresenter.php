<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule;

use App\AccountancyModule\Components\CashbookControl;
use App\AccountancyModule\Factories\ICashbookControlFactory;
use App\Forms\BaseForm;
use Cake\Chronos\Date;
use Model\Auth\Resources\Camp;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\ParticipantType;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CampParticipantCategoryIdQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\FinalCashBalanceQuery;
use Model\DTO\Cashbook\Chit;
use Model\Event\Commands\Camp\ActivateAutocomputedCashbook;
use Model\Event\SkautisCampId;
use Money\Money;
use Skautis\Wsdl\PermissionException;
use function count;

class CashbookPresenter extends BasePresenter
{
    /** @var bool */
    private $missingCategories;

    /** @var ICashbookControlFactory */
    private $cashbookFactory;

    public function __construct(ICashbookControlFactory $cashbookFactory)
    {
        parent::__construct();
        $this->cashbookFactory = $cashbookFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->isEditable        = $this->isEditable || $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $this->aid);
        $this->missingCategories = ! $this->event->IsRealTotalCostAutoComputed; // je aktivní dopočítávání?
    }

    public function renderDefault(int $aid) : void
    {
        /** @var Money $finalBalance */
        $finalBalance = $this->queryBus->handle(new FinalCashBalanceQuery($this->getCashbookId()));

        $this->template->setParameters(
            [
            'isCashbookEmpty' => $this->isCashbookEmpty(),
            'cashbookId' => $this->getCashbookId()->toString(),
            'isInMinus' => $finalBalance->isNegative(),
            'isEditable' => $this->isEditable,
            'missingCategories' => $this->missingCategories,
            ]
        );
    }

    public function handleActivateAutocomputedCashbook(int $aid) : void
    {
        try {
            $this->commandBus->handle(new ActivateAutocomputedCashbook(new SkautisCampId($aid)));
            $this->flashMessage('Byl aktivován automatický výpočet příjmů a výdajů v rozpočtu.');
        } catch (PermissionException $e) {
            $this->flashMessage('Dopočítávání se nepodařilo aktivovat. Pro aktivaci musí být tábor alespoň ve stavu schváleno střediskem.', 'danger');
        }

        $this->redirect('this');
    }

    protected function createComponentCashbook() : CashbookControl
    {
        return $this->cashbookFactory->create($this->getCashbookId(), $this->isEditable && ! $this->missingCategories);
    }

    protected function createComponentFormImportHpd() : BaseForm
    {
        $form = new BaseForm();
        $form->addRadioList('cat', 'Kategorie:', ['child' => 'Od dětí a roverů', 'adult' => 'Od dospělých'])
            ->addRule($form::FILLED, 'Musíte vyplnit kategorii.')
            ->setDefaultValue('child');
        $form->addRadioList('isAccount', 'Placeno:', ['N' => 'Hotově', 'Y' => 'Přes účet'])
            ->addRule($form::FILLED, 'Musíte vyplnit způsob platby.')
            ->setDefaultValue('N');

        $form->addSubmit('send', 'Importovat')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (BaseForm $form) : void {
            $this->formImportHpdSubmitted($form);
        };

        $form->setDefaults(['category' => 'un']);

        return $form;
    }

    private function formImportHpdSubmitted(BaseForm $form) : void
    {
        $this->editableOnly();
        $values = $form->getValues();

        $aid    = $this->getCurrentUnitId();
        $amount = $this->eventService->participants->getCampTotalPayment($aid, $values->cat, $values->isAccount);

        if ($amount === 0.0) {
            $this->flashMessage('Nemáte žádné příjmy od účastníků, které by bylo možné importovat.', 'warning');
            $this->redirect('default', ['aid' => $aid]);
        }

        $date    = $this->eventService->event->get($aid)->StartDate;
        $purpose = 'úč. příspěvky ' . ($values->isAccount === 'Y' ? '- účet' : '- hotovost');
        $body    = new ChitBody(null, new Date($date), null, Amount::fromFloat($amount), $purpose);

        $categoryId = $this->queryBus->handle(
            new CampParticipantCategoryIdQuery(new SkautisCampId($aid), ParticipantType::get(ParticipantType::CHILD))
        );

        $this->commandBus->handle(new AddChitToCashbook($this->getCashbookId(), $body, $categoryId, PaymentMethod::CASH()));

        $this->flashMessage('HPD byl importován');

        $this->redirect('default', $aid);
    }

    private function getCashbookId() : CashbookId
    {
        return $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($this->aid)));
    }

    private function isCashbookEmpty() : bool
    {
        /** @var Chit[] $chits */
        $chits = $this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $this->getCashbookId()));

        return count($chits) === 0;
    }
}
