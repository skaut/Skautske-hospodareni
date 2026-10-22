<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Accessory\Components;

use App\Components\Dialog;
use App\Model\Cashbook\Commands\Unit\ActivateCashbook;
use App\Model\Cashbook\ReadModel\Queries\ActiveUnitCashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\Common\UnitId;
use App\Model\DTO\Cashbook\UnitCashbook;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Application\Attributes\Persistent;
use Nette\Utils\ArrayHash;

use function sprintf;

class ActivateCashbookDialog extends Dialog
{
    #[Persistent]
    public bool $opened = false;

    /** @var callable[] */
    public array $onSuccess = [];

    public function __construct(private bool $isEditable, private UnitId $unitId, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/ActivateCashbookDialog.latte');
        $this->template->setParameters([
            'renderModal' => $this->opened,
        ]);
    }

    public function open(): void
    {
        $this->opened = true;
        $this->redrawControl();
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addSelect('cashbookId', 'Pokladní kniha', $this->getCashbooks())
            ->setDefaultValue($this->getActiveCashbook()->getId())
            ->setRequired();

        $form->addSubmit('create', 'Vybrat')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function ($_x, ArrayHash $values): void {
            $this->formSucceeded($values->cashbookId);
        };

        return $form;
    }

    private function formSucceeded(int $cashbookId): void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat pokladní knihy', 'danger');
            $this->redirect('this', ['opened' => false]);
        }

        $this->commandBus->handle(new ActivateCashbook($this->unitId, $cashbookId));

        $this->flashMessage(
            sprintf(
                'html: Pokladní kniha <strong>%d</strong> byla nastavena jako výchozí.',
                $this->getActiveCashbook()->getYear(),
            ),
        );
        $this->opened = false;
        $this->redirect('this');
    }

    /** @return string[] cashbook ID => cashbook year */
    private function getCashbooks(): array
    {
        $cashbooks = $this->queryBus->handle(new UnitCashbookListQuery($this->unitId));
        $pairs = [];

        foreach ($cashbooks as $cashbook) {
            if (! $cashbook instanceof UnitCashbook) {
                throw new LogicException('Assertion failed.');
            }
            $pairs[$cashbook->getId()] = (string) $cashbook->getYear();
        }

        return $pairs;
    }

    private function getActiveCashbook(): UnitCashbook
    {
        return $this->queryBus->handle(new ActiveUnitCashbookQuery($this->unitId));
    }
}
