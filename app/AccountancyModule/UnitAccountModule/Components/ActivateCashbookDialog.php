<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Commands\Unit\ActivateCashbook;
use Model\Cashbook\ReadModel\Queries\ActiveUnitCashbookQuery;
use Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use Model\Common\UnitId;
use Model\DTO\Cashbook\UnitCashbook;
use Nette\Utils\ArrayHash;
use function assert;
use function sprintf;

final class ActivateCashbookDialog extends BaseControl
{
     /** @persistent */
    public bool $opened = false;

    /** @var callable[] */
    public $onSuccess = [];

    private bool $isEditable;

    private UnitId $unitId;

    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(bool $isEditable, UnitId $unitId, CommandBus $commandBus, QueryBus $queryBus)
    {
        parent::__construct();
        $this->isEditable = $isEditable;
        $this->unitId     = $unitId;
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/ActivateCashbookDialog.latte');
        $this->template->setParameters([
            'renderModal' => $this->opened,
        ]);
        $this->template->render();
    }

    public function open() : void
    {
        $this->opened = true;
        $this->redrawControl();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addSelect('cashbookId', 'Pokladní kniha', $this->getCashbooks())
            ->setDefaultValue($this->getActiveCashbook()->getId())
            ->setRequired();

        $form->addSubmit('create', 'Vybrat')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function ($_, ArrayHash $values) : void {
            $this->formSucceeded($values->cashbookId);
        };

        return $form;
    }

    private function formSucceeded(int $cashbookId) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat pokladní knihy', 'danger');
            $this->redirect('this', ['opened' => false]);
        }

        $this->commandBus->handle(new ActivateCashbook($this->unitId, $cashbookId));

        $this->flashMessage(
            sprintf(
                'html: Pokladní kniha <strong>%d</strong> byla nastavena jako výchozí.',
                $this->getActiveCashbook()->getYear()
            )
        );
        $this->opened = false;
        $this->redirect('this');
    }

    /**
     * @return string[] cashbook ID => cashbook year
     */
    private function getCashbooks() : array
    {
        $cashbooks = $this->queryBus->handle(new UnitCashbookListQuery($this->unitId));
        $pairs     = [];

        foreach ($cashbooks as $cashbook) {
            assert($cashbook instanceof UnitCashbook);

            $pairs[$cashbook->getId()] = (string) $cashbook->getYear();
        }

        return $pairs;
    }

    private function getActiveCashbook() : UnitCashbook
    {
        return $this->queryBus->handle(new ActiveUnitCashbookQuery($this->unitId));
    }
}
