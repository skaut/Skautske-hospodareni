<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\AccountancyModule\Components\Cashbook\MoveChitsDialog;
use App\AccountancyModule\Factories\Cashbook\IMoveChitsDialogFactory;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookNumberPrefixQuery;
use Model\Cashbook\ReadModel\Queries\CashbookTypeQuery;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Nette\InvalidStateException;

class CashbookControl extends BaseControl
{

    /** @var int */
    private $cashbookId;

    /**
     * Can current user edit cashbook?
     * @var bool
     */
    private $isEditable;

    /** @var QueryBus */
    private $queryBus;

    /** @var IMoveChitsDialogFactory */
    private $moveChitsDialogFactory;

    public function __construct(int $cashbookId, bool $isEditable, QueryBus $queryBus, IMoveChitsDialogFactory $moveChitsDialogFactory)
    {
        parent::__construct();
        $this->cashbookId = $cashbookId;
        $this->isEditable = $isEditable;
        $this->queryBus = $queryBus;
        $this->moveChitsDialogFactory = $moveChitsDialogFactory;
    }

    public function render(): void
    {
        $this->template->setParameters([
            'cashbookId'    => $this->cashbookId,
            'isEditable'    => $this->isEditable,
            'canMoveChits'  => $this->canMoveChits(),
            'canMassExport' => $this->canMassExport(),
            'aid'           => (int) $this->getPresenter()->getParameter('aid'), // TODO: rework actions to use cashbook ID
            'chits'         => $this->queryBus->handle(new ChitListQuery($this->cashbookId)),
            'prefix'        => $this->queryBus->handle(new CashbookNumberPrefixQuery($this->cashbookId)),
            'categories'    => $this->queryBus->handle(new CategoryListQuery($this->cashbookId)),
        ]);

        $this->template->setFile(__DIR__ . '/templates/CashbookControl.latte');
        $this->template->render();
    }

    protected function createComponentFormMass(): BaseForm
    {
        $form = new BaseForm();
        $form->getElementPrototype()->setAttribute('class', 'ajax');

        $printButton = $form->addSubmit('massPrintSend');
        $exportButton = $form->addSubmit('massExportSend');
        $moveChitsButton = $form->addSubmit('massMoveSend');

        $form->onSuccess[] = function(BaseForm $form) use ($printButton, $exportButton, $moveChitsButton) : void {
            $chitIds = $form->getHttpData($form::DATA_TEXT, 'chits[]');
            $chitIds = array_map('\intval', $chitIds);

            if ($printButton->isSubmittedBy()) {
                $this->redirectToExport(':Accountancy:Export:printChits', $chitIds);
                return;
            }

            if ($exportButton->isSubmittedBy()) {
                $this->redirectToExport(':Accountancy:Export:exportChits', $chitIds);
                return;
            }

            if ($moveChitsButton->isSubmittedBy()) {
                $this['moveChitsDialog']->open($chitIds);
            }
        };

        return $form;
    }

    protected function createComponentMoveChitsDialog(): MoveChitsDialog
    {
        if ( ! $this->canMoveChits()) {
            throw new InvalidStateException("Can't create move dialog for unit cashbook");
        }

        return $this->moveChitsDialogFactory->create($this->cashbookId);
    }

    /**
     * @param int[] $chitIds
     */
    private function redirectToExport(string $action, array $chitIds): void
    {
        $this->presenter->redirect($action, [$this->cashbookId, $chitIds]);
    }

    private function canMoveChits(): bool
    {

        return ! $this->getSkautisType()->equalsValue(ObjectType::UNIT);
    }

    private function canMassExport(): bool
    {
        return $this->getSkautisType()->equalsValue(ObjectType::UNIT);
    }

    private function getSkautisType(): ObjectType
    {
        /** @var CashbookType $cashbookType */
        $cashbookType = $this->queryBus->handle(new CashbookTypeQuery($this->cashbookId));

        return $cashbookType->getSkautisObjectType();
    }

}
