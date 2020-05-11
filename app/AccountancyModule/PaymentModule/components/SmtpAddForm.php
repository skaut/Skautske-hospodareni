<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use Model\Common\UnitId;
use Model\Payment\Commands\CreateMailCredentials;
use Model\Payment\EmailNotSet;
use Model\Payment\MailCredentials\MailProtocol;
use Nette\Application\UI\Form;
use Nette\Mail\SmtpException;

final class SmtpAddForm extends BaseControl
{
    /** @var UnitId */
    private $unitId;

    /** @var int */
    private $userId;

    /** @var CommandBus */
    private $commandBus;

    public function __construct(UnitId $unitId, int $userId, CommandBus $commandBus)
    {
        parent::__construct();
        $this->unitId     = $unitId;
        $this->userId     = $userId;
        $this->commandBus = $commandBus;
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/SmtpAddForm.latte');
        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addText('host', 'Host')
            ->addRule(Form::FILLED, 'Musíte vyplnit pole host.')
            ->getControlPrototype()->placeholder('např. smtp.gmail.com');
        $form->addText('username', 'Uživatelské jméno')
            ->addRule(Form::FILLED, 'Musíte vyplnit uživatelské jméno.')
            ->getControlPrototype()->placeholder('např. platby@stredisko.cz');
        $form->addText('password', 'Heslo')
            ->addRule(Form::FILLED, 'Musíte vyplnit heslo.');
        $form->addSelect(
            'secure',
            'Zabezpečení',
            [
                MailProtocol::SSL => 'ssl',
                MailProtocol::TLS => 'tls',
            ]
        );
        $form->addText('sender', 'Email odesílatele')
            ->setRequired('Musíte vyplnit email odesílatele')
            ->addRule($form::EMAIL, 'Email odesílatele není platná emailová adresa')
            ->getControlPrototype()->placeholder('např. platby@stredisko.cz');
        $form->addSubmit('send', 'Založit')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (BaseForm $form) : void {
            $this->formSucceeded($form);
        };

        return $form;
    }

    private function formSucceeded(BaseForm $form) : void
    {
        $v = $form->getValues();

        try {
            $this->commandBus->handle(
                new CreateMailCredentials(
                    $this->unitId->toInt(),
                    $v->host,
                    $v->username,
                    $v->password,
                    MailProtocol::get($v->secure),
                    $v->sender,
                    $this->userId
                )
            );

            $this->flashMessage('SMTP účet byl přidán');
        } catch (SmtpException $e) {
            $this->flashMessage('K SMTP účtu se nepodařilo připojit (' . $e->getMessage() . ')', 'danger');
        } catch (EmailNotSet $e) {
            $this->flashMessage('Nemáte nastavený email ve skautisu, na který by se odeslal testovací email!');
        }

        $this->getPresenter()->redirect('Mail:default', ['unitId' => $this->unitId->toInt()]);
    }
}
