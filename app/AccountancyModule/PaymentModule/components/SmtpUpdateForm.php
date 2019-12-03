<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use Model\Common\UnitId;
use Model\Payment\Commands\UpdateMailPassword;
use Nette\Application\UI\Form;

final class SmtpUpdateForm extends BaseControl
{
    /** @var UnitId */
    private $unitId;

    /** @var CommandBus */
    private $commandBus;

    /**
     * (string because persistent parameters aren't auto-casted)
     *
     * @var        int|string
     * @persistent
     */
    public $smtpId;

    public function __construct(UnitId $unitId, CommandBus $commandBus)
    {
        parent::__construct();
        $this->unitId     = $unitId;
        $this->commandBus = $commandBus;
    }

    public function handleOpen(int $smtpId) : void
    {
        $this->smtpId = $smtpId;
        $this->redrawControl();
    }

    public function render() : void
    {
        $this->template->setParameters([
            'renderModal' => $this->smtpId !== null,
        ]);
        $this->template->setFile(__DIR__ . '/templates/SmtpUpdateForm.latte');
        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();
        $form->useBootstrap4();
        $form->addText('password', 'Nové heslo')
            ->addRule(Form::FILLED, 'Musíte vyplnit heslo.');
        $form->addHidden('smtpId', $this->smtpId);
        $form->addSubmit('send', 'Nastavit heslo')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (BaseForm $form) : void {
            $this->formSucceeded($form);
        };

        return $form;
    }

    private function formSucceeded(BaseForm $form) : void
    {
        $v = $form->getValues();

        $this->commandBus->handle(new UpdateMailPassword((int) $v->smtpId, $v->password));
        $this->flashMessage('SMTP heslo bylo upraveno');
        $this->getPresenter()->redirect('Mail:default', ['unitId' => $this->unitId->toInt()]);
    }
}
