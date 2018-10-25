<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook;

use App\AccountancyModule\Components\BaseControl;
use App\AccountancyModule\Factories\Cashbook\IInvertChitDialogFactory;
use App\AccountancyModule\Factories\Cashbook\IMoveChitsDialogFactory;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ChitLocked;
use Model\Cashbook\ChitNotFound;
use Model\Cashbook\Commands\Cashbook\RemoveChitFromCashbook;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Nette\InvalidStateException;
use function array_map;

/**
 * @method onEditButtonClicked(int $chitId)
 */
class ChitListControl extends BaseControl
{
    /** @var callable[] */
    public $onEditButtonClicked = [];

    /** @var CashbookId */
    private $cashbookId;

    /**
     * Can current user edit cashbook?
     *
     * @var bool
     */
    private $isEditable;

    /** @var PaymentMethod */
    private $paymentMethod;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    /** @var IMoveChitsDialogFactory */
    private $moveChitsDialogFactory;

    /** @var IInvertChitDialogFactory */
    private $invertChitDialogFactory;

    public function __construct(
        CashbookId $cashbookId,
        bool $isEditable,
        PaymentMethod $paymentMethod,
        CommandBus $commandBus,
        QueryBus $queryBus,
        IMoveChitsDialogFactory $moveChitsDialogFactory,
        IInvertChitDialogFactory $invertChitDialogFactory
    ) {
        parent::__construct();
        $this->cashbookId              = $cashbookId;
        $this->isEditable              = $isEditable;
        $this->paymentMethod           = $paymentMethod;
        $this->commandBus              = $commandBus;
        $this->queryBus                = $queryBus;
        $this->moveChitsDialogFactory  = $moveChitsDialogFactory;
        $this->invertChitDialogFactory = $invertChitDialogFactory;
    }

    public function render() : void
    {
        /** @var Cashbook $cashbook */
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));
        $chits    = $this->queryBus->handle(ChitListQuery::withMethod($this->paymentMethod, $this->cashbookId));
        $totals   = $this->getTotals($chits);
        $this->template->setParameters(
            [
            'cashbookId' => $this->cashbookId->toString(),
            'cashbookType' => $cashbook->getType(),
            'isEditable' => $this->isEditable,
            'canMoveChits' => $this->canMoveChits(),
            'canMassExport' => $this->canMassExport(),
            'aid' => (int) $this->getPresenter()->getParameter('aid'), // TODO: rework actions to use cashbook ID
            'chits' => $chits,
            'paymentMethod' => $this->paymentMethod,
            'prefix' => $cashbook->getChitNumberPrefix(),
            'validInverseCashbookTypes' => InvertChitDialog::getValidInverseCashbookTypes(),
            'totalIncome' => $totals[Operation::INCOME],
            'totalExpense' => $totals[Operation::EXPENSE],
            ]
        );

        $this->template->setFile(__DIR__ . '/templates/ChitListControl.latte');
        $this->template->render();
    }

    public function handleRemove(int $chitId) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat pokladní knihu.', 'danger');
            $this->redirect('this');
        }

        try {
            $this->commandBus->handle(new RemoveChitFromCashbook($this->cashbookId, $chitId));
            $this->flashMessage('Paragon byl smazán');
        } catch (ChitLocked $e) {
            $this->flashMessage('Nelze smazat zamčený paragon', 'error');
        } catch (CashbookNotFound | ChitNotFound $e) {
            $this->flashMessage('Paragon se nepodařilo smazat');
        }

        $this->redirect('this');
    }

    public function handleEdit(int $chitId) : void
    {
        $this->onEditButtonClicked($chitId);
    }

    protected function createComponentFormMass() : BaseForm
    {
        $form = new BaseForm();
        $form->getElementPrototype()->setAttribute('class', 'ajax');

        $printButton     = $form->addSubmit('massPrintSend');
        $exportButton    = $form->addSubmit('massExportSend');
        $moveChitsButton = $form->addSubmit('massMoveSend');

        $form->onSuccess[] = function (BaseForm $form) use ($printButton, $exportButton, $moveChitsButton) : void {
            $chitIds = $form->getHttpData($form::DATA_TEXT, 'chits-' . $this->paymentMethod . '[]');
            $chitIds = array_map('\intval', $chitIds);

            if ($printButton->isSubmittedBy()) {
                $this->redirectToExport(':Accountancy:CashbookExport:printChits', $chitIds);
                return;
            }

            if ($exportButton->isSubmittedBy()) {
                $this->redirectToExport(':Accountancy:CashbookExport:exportChits', $chitIds);
                return;
            }

            if (! $moveChitsButton->isSubmittedBy()) {
                return;
            }

            $this['moveChitsDialog']->open($chitIds);
        };

        return $form;
    }

    protected function createComponentMoveChitsDialog() : MoveChitsDialog
    {
        if (! $this->canMoveChits()) {
            throw new InvalidStateException("Can't create move dialog for unit cashbook");
        }

        return $this->moveChitsDialogFactory->create($this->cashbookId);
    }

    protected function createComponentInvertChitDialog() : InvertChitDialog
    {
        return $this->invertChitDialogFactory->create($this->cashbookId);
    }

    /**
     * @param int[] $chitIds
     */
    private function redirectToExport(string $action, array $chitIds) : void
    {
        $this->presenter->redirect($action, [$this->cashbookId->toString(), $chitIds]);
    }

    private function canMoveChits() : bool
    {
        return ! $this->getSkautisType()->equalsValue(ObjectType::UNIT);
    }

    private function canMassExport() : bool
    {
        return $this->getSkautisType()->equalsValue(ObjectType::UNIT);
    }

    private function getSkautisType() : ObjectType
    {
        /** @var Cashbook $cashbook */
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));
        return $cashbook->getType()->getSkautisObjectType();
    }

    /**
     * @param Chit[] $chits
     * @return float[]
     */
    private function getTotals(array $chits) : array
    {
        $income  = 0;
        $expense = 0;
        foreach ($chits as $chit) {
            if ($chit->getCategory()->getOperationType()->equalsValue(Operation::INCOME)) {
                $income += $chit->getBody()->getAmount()->toFloat();
            } else {
                $expense += $chit->getBody()->getAmount()->toFloat();
            }
        }
        return [
            Operation::INCOME => $income,
            Operation::EXPENSE => $expense,
        ];
    }
}
