<?php

namespace App\AccountancyModule\PaymentModule;

use App\Forms\BaseForm;
use Model\MailService;
use Model\Payment\Commands\CreateMailCredentials;
use Model\Payment\Commands\RemoveMailCredentials;
use Model\Payment\MailCredentials\MailProtocol;
use Nette\Application\UI\Form;

/**
 * @author Hána František <sinacek@gmail.com>
 */
class MailPresenter extends BasePresenter
{

    /** @var MailService */
    private $model;

    public function __construct(MailService $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function renderDefault(int $aid): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění přistupovat ke správě emailů", "danger");
            $this->redirect("Payment:default");
        }
        $this->template->list = $this->model->getAll($this->aid);
        $this->template->editableUnits = $this->getEditableUnits();
    }

    public function handleEdit($id): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění měnit smtp", "danger");
            $this->redirect("Payment:default");
        }
    }

    public function handleRemove(int $id): void
    {
        $mail = $this->model->get($id);

        if (!$this->isEditable || $mail->getUnitId() !== $this->aid) {
            $this->flashMessage("Nemáte oprávnění mazat smtp", "danger");
            $this->redirect("Payment:default");
        }

        $this->commandBus->handle(new RemoveMailCredentials($id));
    }

    protected function createComponentFormCreate(): Form
    {
        $form = new BaseForm();
        $form->addText("host", "Host")
            ->addRule(Form::FILLED, "Musíte vyplnit pole host.")
            ->getControlPrototype()->placeholder("např. smtp.gmail.com");
        $form->addText("username", "Už. jméno")
            ->addRule(Form::FILLED, "Musíte vyplnit uživatelské jméno.")
            ->getControlPrototype()->placeholder("např. platby@stredisko.cz");
        $form->addText("password", "Heslo")
            ->addRule(Form::FILLED, "Musíte vyplnit heslo.");
        $form->addSelect("secure", "Zabezpečení", [
            MailProtocol::SSL => "ssl",
            MailProtocol::TLS => "tls"
        ]);
        $form->addSubmit('send', 'Založit')
            ->setAttribute("class", "btn btn-primary");

        $form->onSuccess[] = function (Form $form): void {
            $this->formCreateSubmitted($form);
        };

        return $form;
    }

    private function formCreateSubmitted(Form $form): void
    {
        if (!$this->isEditable) {
            $this->flashMessage("Nemáte oprávnění přidávat smtp", "danger");
            $this->redirect("Payment:default");
        }
        $v = $form->getValues();

        $userId = $this->user->getId();
        try {
            $this->commandBus->handle(
                new CreateMailCredentials(
                    $this->aid,
                    $v->host,
                    $v->username,
                    $v->password,
                    MailProtocol::get($v->secure),
                    $userId
                )
            );

            $this->flashMessage("SMTP účet byl přidán");
        } catch (\Nette\Mail\SmtpException $e) {
            $this->flashMessage("K SMTP účtu se nepodařilo připojit (" . $e->getMessage() . ")", "danger");
        } catch (\Model\Payment\EmailNotSetException $e) {
            $this->flashMessage('Nemáte nastavený email ve skautisu, na který by se odeslal testovací email!');
        }

        $this->redirect("this");
    }

}
