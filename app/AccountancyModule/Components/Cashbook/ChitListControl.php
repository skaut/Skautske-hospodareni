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
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\DTO\Cashbook\Cashbook;
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
        CommandBus $commandBus,
        QueryBus $queryBus,
        IMoveChitsDialogFactory $moveChitsDialogFactory,
        IInvertChitDialogFactory $invertChitDialogFactory
    ) {
        parent::__construct();
        $this->cashbookId              = $cashbookId;
        $this->isEditable              = $isEditable;
        $this->commandBus              = $commandBus;
        $this->queryBus                = $queryBus;
        $this->moveChitsDialogFactory  = $moveChitsDialogFactory;
        $this->invertChitDialogFactory = $invertChitDialogFactory;
    }

    public function render() : void
    {
        /**
 * @var Cashbook $cashbook
*/
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));
        $this->template->setParameters(
            [
            'cashbookId' => $this->cashbookId->toInt(),
            'isEditable' => $this->isEditable,
            'canMoveChits' => $this->canMoveChits(),
            'canMassExport' => $this->canMassExport(),
            'aid' => (int) $this->getPresenter()->getParameter('aid'), // TODO: rework actions to use cashbook ID
            'chits' => $this->queryBus->handle(new ChitListQuery($this->cashbookId, PaymentMethod::get(PaymentMethod::CASH))),
            'prefix' => $cashbook->getChitNumberPrefix(),
            'validInverseCashbookTypes' => InvertChitDialog::getValidInverseCashbookTypes(),
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
            $chitIds = $form->getHttpData($form::DATA_TEXT, 'chits[]');
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
        $this->presenter->redirect($action, [$this->cashbookId->toInt(), $chitIds]);
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
        /**
 * @var Cashbook $cashbook
*/
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));
        return $cashbook->getType()->getSkautisObjectType();
    }
}
