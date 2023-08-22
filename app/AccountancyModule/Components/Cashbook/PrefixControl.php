<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook;

use App\AccountancyModule\Components\Dialog;
use App\Forms\BaseForm;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Commands\Cashbook\UpdateChitNumberPrefix;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Common\Services\CommandBus;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Cashbook;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

use function assert;

final class PrefixControl extends Dialog
{
    private const MAX_LENGTH = 6;

    public function __construct(private CashbookId $cashbookId, private PaymentMethod $paymentMethod, private bool $isEditable, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    public function handleOpen(): void
    {
        $this->show();
    }

    public function beforeRender(): void
    {
        $this->template->setFile(__DIR__ . '/templates/PrefixControl.latte');
        $this->template->setParameters([
            'isEditable' => $this->isEditable,
            'prefix' => $this->getPrefix(),
            'editing' => $this->opened,
        ]);
    }

    protected function createComponentForm(): BaseForm
    {
        if (! $this->isEditable) {
            throw new BadRequestException('User cannot edit cashbook prefix', IResponse::S403_Forbidden);
        }

        $form = new BaseForm();

        $form->addText('prefix')
            ->setRequired(false)
            ->setMaxLength(self::MAX_LENGTH)
            ->setHtmlAttribute('size', 6)
            ->setNullable()
            ->setDefaultValue($this->getPrefix())
            ->addRule(BaseForm::MAX_LENGTH, 'Maximální délka prefixu je %d znaků', self::MAX_LENGTH);

        $form->addSubmit('submit', 'Uložit');

        $form->onSuccess[] = function ($_x, array $values): void {
            $this->commandBus->handle(new UpdateChitNumberPrefix($this->cashbookId, $this->paymentMethod, $values['prefix']));
            $this->hide();
        };

        return $form;
    }

    private function getPrefix(): string|null
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getChitNumberPrefix($this->paymentMethod);
    }
}
