<?php

declare(strict_types=1);

namespace App\Components\Cashbook;

use App\Components\Dialog;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\Commands\Cashbook\UpdateChitNumberPrefix;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Common\Services\CommandBus;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Cashbook;
use Component\Forms\BaseForm;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

use function assert;

final class PrefixControl extends Dialog
{
    private const MAX_LENGTH = 6;

    public function __construct(private CashbookId $cashbookId, private PaymentMethod $paymentMethod, private bool $isEditable, private CommandBus $commandBus, private QueryBus $queryBus)
    {
    }

    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->setFile(__DIR__.'/templates/PrefixControl.latte');
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

    private function getPrefix(): ?string
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getChitNumberPrefix($this->paymentMethod);
    }
}
