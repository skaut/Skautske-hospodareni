<?php

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Factories\FormFactory;
use App\Forms\BaseContainer;
use App\Forms\BaseForm;
use Model\PaymentService;
use Nette\Application\UI\Control;

class MassAddForm extends Control
{

    /** @var int */
    private $groupId;

    /** @var FormFactory */
    private $formFactory;

    /** @var PaymentService */
    private $payments;

    public function __construct(int $groupId, FormFactory $formFactory, PaymentService $payments)
    {
        parent::__construct();
        $this->groupId = $groupId;
        $this->formFactory = $formFactory;
        $this->payments = $payments;
    }


    protected function createComponentForm(): BaseForm
    {
        $form = $this->formFactory->create();

        $form->addText("amount", "Částka:")
            ->setAttribute('class', 'input-mini');

        $form->addDatePicker("dueDate", "Splatnost:")
            ->setAttribute('class', 'input-small');
        $form->addText("constantSymbol", "KS:")
            ->setMaxLength(4)
            ->setAttribute('class', 'input-mini');
        $form->addText("note", "Poznámka:")
            ->setAttribute('class', 'input-small');

        $form->addContainer("persons");

        $form->addSubmit('send', 'Přidat vybrané')
            ->setAttribute("class", "btn btn-primary btn-large");

        $form->onSubmit[] = function (BaseForm $form): void {
            $this->formSubmitted($form);
        };

        $group = $this->payments->getGroup($this->groupId);

        $form->setDefaults([
            "amount" => $group->getDefaultAmount(),
            "dueDate" => $group->getDueDate() !== NULL ? $group->getDueDate()->format('d.m.Y') : NULL,
            "constantSymbol" => $group->getConstantSymbol(),
        ]);

        return $form;
    }

    public function addPerson(int $id, array $emails, string $name, ?float $amount = NULL, string $note = ""): void
    {
        $form = $this["form"]; /* @var $form BaseForm */
        $persons = $form["persons"]; /* @var $persons BaseContainer */

        $container = $persons->addContainer("person{$id}");

        $container->addCheckbox("selected");

        $container->addSelect("email", NULL, $emails)
            ->setRequired(FALSE);

        $container->addText("name")
            ->setRequired("Musíte vyplnit jméno")
            ->setDefaultValue($name);

        $container->addHidden("id", $id);

        $container->addText("amount", "Částka:")
            ->setAttribute('class', 'input-mini')
            ->setType('number')
            ->setRequired(FALSE)
            ->setDefaultValue($amount)
            ->addConditionOn($container["selected"], $form::FILLED)
            ->addConditionOn($form["amount"], $form::BLANK)
                ->setRequired("Musíte vyplnit částku")
                ->addRule($form::FLOAT, "Částka musí být číslo");

        $container->addDatePicker('dueDate', "Splatnost:")
            ->setAttribute('class', 'input-small')
            ->setRequired(FALSE);

        $container->addText("variableSymbol", "VS:")
            ->setRequired(FALSE)
            ->addRule($form::INTEGER)
            ->addRule($form::MAX_LENGTH, "Maximální délka konstantního symbolu je %d", 10);

        $container->addText("constantSymbol", "KS:")
            ->setAttribute('class', 'input-mini')
            ->setRequired(FALSE)
            ->addRule($form::INTEGER)
            ->addRule($form::MAX_LENGTH, "Maximální délka konstantního symbolu je %d", 4);

        $container->addText("note", "Poznámka:")
            ->setDefaultValue($note)
            ->setRequired(FALSE)
            ->setAttribute('class', 'input-small');
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__.'/templates/MassAddForm.latte');
        $this->template->render();
    }

    private function formSubmitted(BaseForm $form): void
    {
        $values = $form->getValues();

        $persons = array_filter((array)$values->persons, function($person) {
            return $person->selected === TRUE;
        });

        if (empty($persons)) {
            $form->addError("Nebyla vybrána žádná osoba k přidání!");
            return;
        }

        if($values->dueDate === NULL) {
            $withoutDueDate = array_filter($persons, function($person) {
                return $person->dueDate === NULL;
            });
            if(!empty($withoutDueDate)) {
                $form->addError("Musíte vyplnit datum splatnosti");
                return;
            }
        }

        foreach($persons as $person) {
            $this->payments->createPayment(
                $this->groupId,
                $person->name,
                $person->email,
                $this->floatOrNull($person->amount) ?? (float)$values->amount,
                \DateTimeImmutable::createFromMutable($person->dueDate ?? $values->dueDate),
                $person->id,
                $this->intOrNull($person->variableSymbol),
                $this->intOrNull($person->constantSymbol) ?? $this->intOrNull($values->constantSymbol),
                $person->note
                );
        }

        $this->presenter->flashMessage("Platby byly přidány");
        $this->presenter->redirect("Payment:detail", ["id" => $this->groupId]);
    }

    private function intOrNull(string $value): ?int
    {
        return $value === "" ? NULL : (int)$value;
    }

    private function floatOrNull(string $value): ?float
    {
        return $value === "" ? NULL : (float)$value;
    }

}
