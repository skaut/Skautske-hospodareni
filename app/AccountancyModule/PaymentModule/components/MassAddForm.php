<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseContainer;
use App\Forms\BaseForm;
use Cake\Chronos\ChronosDate;
use Model\Common\EmailAddress;
use Model\Common\Services\CommandBus;
use Model\Payment\Commands\Payment\CreatePayment;
use Model\PaymentService;
use Nette\Forms\Controls\TextBase;

use function array_filter;
use function array_keys;
use function array_map;
use function array_slice;
use function assert;

class MassAddForm extends BaseControl
{
    public function __construct(private int $groupId, private PaymentService $payments, private CommandBus $commandBus)
    {
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addText('amount', 'Částka:')
            ->setNullable()
            ->setRequired(false)
            ->addRule($form::FLOAT, 'Částka musí být číslo')
            ->addRule($form::MIN, 'Čátka musí být větší než 0', 0.01)
            ->setHtmlAttribute('class', 'input-mini');

        $form->addDate('dueDate', 'Splatnost:')
            ->disableWeekends()
            ->setRequired(false)
            ->setHtmlAttribute('class', 'input-small');
        $form->addText('constantSymbol', 'KS:')
            ->setRequired(false)
            ->setNullable()
            ->addRule(BaseForm::INTEGER)
            ->addRule(BaseForm::MAX_LENGTH, 'Maximální délka konstantního symbolu je %d', 4)
            ->setMaxLength(4)
            ->setHtmlAttribute('class', 'input-mini');
        $form->addText('note', 'Poznámka:')
            ->setHtmlAttribute('class', 'input-small');

        $form->addContainer('persons');

        $form->addSubmit('send', 'Přidat vybrané')
            ->setHtmlAttribute('class', 'btn btn-primary btn-large');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formSubmitted($form);
        };

        $group = $this->payments->getGroup($this->groupId);

        $form->setDefaults([
            'amount' => $group->getDefaultAmount(),
            'dueDate' => $group->getDueDate(),
            'constantSymbol' => $group->getConstantSymbol(),
        ]);

        return $form;
    }

    /** @param string[] $emails */
    public function addPerson(int $id, array $emails, string $name, float|null $amount = null, string $note = '', string $variableSymbol = '', ChronosDate|null $dueDate = null): void
    {
        $form          = $this['form'];
        $persons       = $form['persons'];
        $defaultAmount = $form['amount'];

        assert($defaultAmount instanceof TextBase && $persons instanceof BaseContainer);

        $container = $persons->addContainer('person' . $id);

        $selected = $container->addCheckbox('selected');

        $container->addMultiSelect('email', null, $emails)
            ->setDefaultValue(array_slice(array_keys($emails), 0, 1))
            ->setRequired(false);

        $container->addText('name')
            ->setRequired('Musíte vyplnit jméno')
            ->setDefaultValue($name);

        $container->addHidden('id', $id);

        $container->addText('amount', 'Částka:')
            ->setHtmlAttribute('class', 'input-mini')
            ->setHtmlType('number')
            ->setRequired(false)
            ->setNullable()
            ->setDefaultValue($amount)
            ->addConditionOn($selected, $form::FILLED)
            ->addRule($form::FLOAT, 'Částka musí být číslo')
            ->addRule($form::MIN, 'Čátka musí být větší než 0', 0.01)
            ->addConditionOn($defaultAmount, $form::BLANK)
            ->setRequired('Musíte vyplnit částku');

        $container->addDate('dueDate', 'Splatnost:')
            ->disableWeekends()
            ->setHtmlAttribute('class', 'input-small')
            ->setDefaultValue($dueDate)
            ->setRequired(false);

        $container->addVariableSymbol('variableSymbol', 'VS:')
            ->setDefaultValue($variableSymbol)
            ->setRequired(false);

        $container->addText('constantSymbol', 'KS:')
            ->setHtmlAttribute('class', 'input-mini')
            ->setRequired(false)
            ->setNullable()
            ->addRule($form::INTEGER)
            ->addRule($form::MAX_LENGTH, 'Maximální délka konstantního symbolu je %d', 4);

        $container->addText('note', 'Poznámka:')
            ->setDefaultValue($note)
            ->setRequired(false)
            ->setHtmlAttribute('class', 'input-small');
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/MassAddForm.latte');
        $this->template->render();
    }

    private function formSubmitted(BaseForm $form): void
    {
        $values = $form->getValues();

        $persons = array_filter(
            (array) $values->persons,
            function ($person) {
                return $person->selected === true;
            },
        );

        if (empty($persons)) {
            $form->addError('Nebyla vybrána žádná osoba k přidání!');

            return;
        }

        if ($values->dueDate === null) {
            $withoutDueDate = array_filter(
                $persons,
                function ($person) {
                    return $person->dueDate === null;
                },
            );
            if (! empty($withoutDueDate)) {
                $form->addError('Musíte vyplnit datum splatnosti');

                return;
            }
        }

        foreach ($persons as $person) {
            $this->commandBus->handle(
                new CreatePayment(
                    $this->groupId,
                    $person->name,
                    array_map(fn (string $email) => new EmailAddress($email), $person->email),
                    (float) ($person->amount ?? $values->amount),
                    $person->dueDate ?? new ChronosDate($values->dueDate),
                    (int) $person->id,
                    $person->variableSymbol,
                    $person->constantSymbol ?? $values->constantSymbol,
                    $person->note,
                ),
            );
        }

        $this->flashMessage('Platby byly přidány');
        $this->getPresenter()->redirect(':Accountancy:Payment:Payment:default', ['id' => $this->groupId]);
    }
}
