<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\UpdateChitNumberPrefix;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\DTO\Cashbook\Cashbook;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use function assert;

final class PrefixControl extends BaseControl
{
    private const MAX_LENGTH = 6;

    /**
     * @var bool
     * @persistent
     */
    public $showForm = false;

    /** @var CashbookId */
    private $cashbookId;

    /** @var bool */
    private $isEditable;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(CashbookId $cashbookId, bool $isEditable, CommandBus $commandBus, QueryBus $queryBus)
    {
        parent::__construct();
        $this->isEditable = $isEditable;
        $this->cashbookId = $cashbookId;
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function render() : void
    {
        $this->template->setParameters([
            'isEditable' => $this->isEditable,
            'prefix' => $this->getPrefix(),
            'showForm' => $this->showForm && $this->isEditable,
        ]);

        $this->template->setFile(__DIR__ . '/templates/PrefixControl.latte');
        $this->template->render();
    }

    public function handleToggleForm(bool $showForm) : void
    {
        $this->showForm = $showForm;
        $this->redrawControl();
    }

    protected function createComponentForm() : BaseForm
    {
        if (! $this->isEditable) {
            throw new BadRequestException('User cannot edit cashbook prefix', IResponse::S403_FORBIDDEN);
        }

        $form = new BaseForm(true);

        $form->addText('prefix')
            ->setRequired(false)
            ->setMaxLength(self::MAX_LENGTH)
            ->setAttribute('size', 6)
            ->setNullable()
            ->setDefaultValue($this->getPrefix())
            ->addRule(BaseForm::MAX_LENGTH, 'Maximální délka předpony je %d znaků', self::MAX_LENGTH);

        $form->addSubmit('submit');

        $form->onSuccess[] = function ($_, array $values) : void {
            $this->commandBus->handle(new UpdateChitNumberPrefix($this->cashbookId, $values['prefix']));
            $this->handleToggleForm(false);
        };

        return $form;
    }

    private function getPrefix() : ?string
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getChitNumberPrefix();
    }
}
