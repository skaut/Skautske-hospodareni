<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\UpdateNote;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\DTO\Cashbook\Cashbook;

final class NoteForm extends BaseControl
{
    /** @var bool @persistent */
    public $editation = FALSE;

    /** @var CashbookId */
    private $cashbookId;

    /**
     * Can current user add/edit chits?
     * @var bool
     */
    private $isEditable;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    public function __construct(
        CashbookId $cashbookId,
        bool $isEditable,
        CommandBus $commandBus,
        QueryBus $queryBus
    )
    {
        parent::__construct();
        $this->cashbookId = $cashbookId;
        $this->isEditable = $isEditable;
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
    }

    public function handleEdit() : void
    {
        $this->editation = TRUE;
        $this->redrawControl();
    }

    public function handleCancel() : void
    {
        $this->editation = FALSE;
        $this->redrawControl();
    }

    public function render() : void
    {
        /** @var Cashbook $cashbook */
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));

        /** @var BaseForm $form */
        $form = $this['form'];
        $form->setDefaults([
            'note' => $cashbook->getNote()
        ]);

        $note = nl2br(htmlspecialchars($cashbook->getNote()));
        $pattern = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
        $note = preg_replace($pattern, '<a href="$0" target="_blank" title="$0">$0</a>', $note);

        $this->template->setParameters([
            'isEditable' => $this->isEditable,
            'editation' => $this->editation,
            'note' => $note,
        ]);

        $this->template->setFile(__DIR__ . '/templates/NoteForm.latte');
        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addText('note')
            ->setRequired(FALSE)
            ->setAttribute('placeholder', 'Libovolná poznámka, kde se odkazy stanou aktivní...')
            ->setAttribute('class', '');

        $form->addButton('save')
            ->setAttribute('type', 'submit')
            ->setAttribute('class', 'btn btn-primary');


        $form->onSuccess[] = function(BaseForm $form) : void {
            $this->editation = FALSE;
            $this->formSucceeded($form);
            $this->redrawControl();
        };
        return $form;
    }

    private function formSucceeded(BaseForm $form) : void
    {
        if(!$this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat pokladní knihu', 'danger');
            $this->redirect('this');
        }
        $this->commandBus->handle(new UpdateNote($this->cashbookId, $form->getValues()->note));

    }


}
