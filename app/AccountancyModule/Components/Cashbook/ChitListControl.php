<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook;

use App\AccountancyModule\Components\BaseControl;
use App\AccountancyModule\Factories\Cashbook\IChitScanControlFactory;
use App\AccountancyModule\Factories\Cashbook\IInvertChitDialogFactory;
use App\AccountancyModule\Factories\Cashbook\IMoveChitsDialogFactory;
use App\AccountancyModule\Factories\Cashbook\IPrefixControlFactory;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ChitLocked;
use Model\Cashbook\ChitNotFound;
use Model\Cashbook\Commands\Cashbook\GenerateChitNumbers;
use Model\Cashbook\Commands\Cashbook\RemoveChitFromCashbook;
use Model\Cashbook\MaxChitNumberNotFound;
use Model\Cashbook\NonNumericChitNumbers;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Nette\Application\UI\Multiplier;
use Nette\InvalidStateException;
use function array_count_values;
use function array_filter;
use function array_map;
use function assert;

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

    /** @var IChitScanControlFactory */
    private $chitScanFactory;

    /** @var IPrefixControlFactory */
    private $prefixFactory;

    public function __construct(
        CashbookId $cashbookId,
        bool $isEditable,
        PaymentMethod $paymentMethod,
        CommandBus $commandBus,
        QueryBus $queryBus,
        IMoveChitsDialogFactory $moveChitsDialogFactory,
        IInvertChitDialogFactory $invertChitDialogFactory,
        IChitScanControlFactory $chitScanControlFactory,
        IPrefixControlFactory $prefixFactory
    ) {
        parent::__construct();
        $this->cashbookId              = $cashbookId;
        $this->isEditable              = $isEditable;
        $this->paymentMethod           = $paymentMethod;
        $this->commandBus              = $commandBus;
        $this->queryBus                = $queryBus;
        $this->moveChitsDialogFactory  = $moveChitsDialogFactory;
        $this->invertChitDialogFactory = $invertChitDialogFactory;
        $this->chitScanFactory         = $chitScanControlFactory;
        $this->prefixFactory           =$prefixFactory;
    }

    public function render() : void
    {
        $cashbook          = $this->queryBus->handle(new CashbookQuery($this->cashbookId));
        $chits             = $this->queryBus->handle(ChitListQuery::withMethod($this->paymentMethod, $this->cashbookId));
        $totals            = $this->getTotals($chits);
        $duplicatesNumbers = $this->findDuplicates($chits);

        assert($cashbook instanceof Cashbook);

        $this->template->setParameters([
            'cashbookId' => $this->cashbookId->toString(),
            'cashbookType' => $cashbook->getType(),
            'isEditable' => $this->isEditable,
            'canMoveChits' => $this->canMoveChits(),
            'canMassExport' => $this->canMassExport(),
            'aid' => (int) $this->getPresenter()->getParameter('aid'), // TODO: rework actions to use cashbook ID
            'chits' => $chits,
            'paymentMethod' => $this->paymentMethod,
            'prefix' => $cashbook->getChitNumberPrefix($this->paymentMethod),
            'validInverseCashbookTypes' => InvertChitDialog::getValidInverseCashbookTypes(),
            'totalIncome' => $totals[Operation::INCOME],
            'totalExpense' => $totals[Operation::EXPENSE],
            'duplicatesNumbers' => $duplicatesNumbers,
            'hasOnlyNumericChitNumbers' => $cashbook->hasOnlyNumericChitNumbers(),
        ]);

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

    public function handleShowLocked() : void
    {
        $this->getPresenter()->redirect(':Accountancy:UnitAccount:Chit:default', ['onlyUnlocked' => 0]);
    }

    public function handleEdit(int $chitId) : void
    {
        $this->onEditButtonClicked($chitId);
    }

    public function handleGenerateNumbers(string $paymentMethod) : void
    {
        try {
            $this->commandBus->handle(new GenerateChitNumbers($this->cashbookId, PaymentMethod::get($paymentMethod)));
            $this->getPresenter()->flashMessage('Čísla paragonů byla dogenerována.');
        } catch (NonNumericChitNumbers $exc) {
            $this->getPresenter()->flashMessage('Nelze generovat čísla, když čísla dokladů jsou nečíselné!', 'error');
        } catch (MaxChitNumberNotFound $exc) {
            $this->getPresenter()->flashMessage('Nepodařilo se určit poslední poslední paragon, od kterého by se pokračovalo s číslováním.', 'error');
        }
        $this->getPresenter()->redirect('this');
    }

    protected function createComponentPrefix() : PrefixControl
    {
        return $this->prefixFactory->create($this->cashbookId, $this->paymentMethod, $this->isEditable);
    }

    protected function createComponentFormMass() : BaseForm
    {
        $form = new BaseForm();

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

    protected function createComponentChitScan() : Multiplier
    {
        return new Multiplier(
            fn (string $chitId) =>
            $this->chitScanFactory->create($this->cashbookId, (int) $chitId, $this->isEditable)
        );
    }

    /**
     * @param int[] $chitIds
     */
    private function redirectToExport(string $action, array $chitIds) : void
    {
        $this->getPresenter()->redirect($action, [$this->cashbookId->toString(), $chitIds]);
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
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getType()->getSkautisObjectType();
    }

    /**
     * @param Chit[] $chits
     *
     * @return float[]
     */
    private function getTotals(array $chits) : array
    {
        $income  = 0;
        $expense = 0;
        foreach ($chits as $chit) {
            if ($chit->isIncome()) {
                $income += $chit->getAmount()->toFloat();
            } else {
                $expense += $chit->getAmount()->toFloat();
            }
        }

        return [
            Operation::INCOME => $income,
            Operation::EXPENSE => $expense,
        ];
    }

    /**
     * @param Chit[] $chits
     *
     * @return int[]
     */
    private function findDuplicates(array $chits) : array
    {
        $duplicates = array_filter(array_count_values(array_map(function (Chit $ch) {
            $number = $ch->getBody()->getNumber();

            return $number === null ? '' : $number->toString();
        }, $chits)), function (int $cnt) {
            return $cnt > 1;
        });
        unset($duplicates['']);

        return $duplicates;
    }
}
