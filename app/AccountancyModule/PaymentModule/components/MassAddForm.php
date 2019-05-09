<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Components;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseContainer;
use App\Forms\BaseForm;
use Model\PaymentService;
use Nette\Forms\Controls\TextBase;
use function array_filter;
use function assert;

class MassAddForm extends BaseControl
{
    /** @var int */
    private $groupId;

    /** @var PaymentService */
    private $payments;

    public function __construct(int $groupId, PaymentService $payments)
    {
        parent::__construct();
        $this->groupId  = $groupId;
        $this->payments = $payments;
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addText('amount', 'Částka:')
            ->setNullable()
            ->setRequired(false)
            ->addRule($form::FLOAT, 'Částka musí být číslo')
            ->addRule($form::MIN, 'Čátka musí být větší než 0', 0.01)
            ->setAttribute('class', 'input-mini');

        $form->addDate('dueDate', 'Splatnost:')
            ->setAttribute('class', 'input-small');
        $form->addText('constantSymbol', 'KS:')
            ->setNullable()
            ->setMaxLength(4)
            ->setAttribute('class', 'input-mini');
        $form->addText('note', 'Poznámka:')
            ->setAttribute('class', 'input-small');

        $form->addContainer('persons');

        $form->addSubmit('send', 'Přidat vybrané')
            ->setAttribute('class', 'btn btn-primary btn-large');

        $form->onSubmit[] = function (BaseForm $form) : void {
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

    /**
     * @param string[] $emails
     */
    public function addPerson(int $id, array $emails, string $name, ?float $amount = null, string $note = '') : void
    {
        $form          = $this['form'];
        $persons       = $form['persons'];
        $defaultAmount = $form['amount'];

        assert($defaultAmount instanceof TextBase && $persons instanceof BaseContainer);

        $container = $persons->addContainer('person' . $id);

        $selected = $container->addCheckbox('selected');

        $container->addSelect('email', null, $emails)
            ->setRequired(false);

        $container->addText('name')
            ->setRequired('Musíte vyplnit jméno')
            ->setDefaultValue($name);

        $container->addHidden('id', $id);

        $container->addText('amount', 'Částka:')
            ->setAttribute('class', 'input-mini')
            ->setType('number')
            ->setRequired(false)
            ->setNullable()
            ->setDefaultValue($amount)
            ->addConditionOn($selected, $form::FILLED)
            ->addConditionOn($defaultAmount, $form::BLANK)
            ->setRequired('Musíte vyplnit částku')
            ->addRule($form::FLOAT, 'Částka musí být číslo')
            ->addRule($form::MIN, 'Čátka musí být větší než 0', 0.01);

        $container->addDate('dueDate', 'Splatnost:')
            ->setAttribute('class', 'input-small')
            ->setRequired(false);

        $container->addVariableSymbol('variableSymbol', 'VS:')
            ->setRequired(false);

        $container->addText('constantSymbol', 'KS:')
            ->setAttribute('class', 'input-mini')
            ->setRequired(false)
            ->setNullable()
            ->addRule($form::INTEGER)
            ->addRule($form::MAX_LENGTH, 'Maximální délka konstantního symbolu je %d', 4);

        $container->addText('note', 'Poznámka:')
            ->setDefaultValue($note)
            ->setRequired(false)
            ->setAttribute('class', 'input-small');
    }

    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/MassAddForm.latte');
        $this->template->render();
    }

    private function formSubmitted(BaseForm $form) : void
    {
        $values = $form->getValues();

        $persons = array_filter(
            (array) $values->persons,
            function ($person) {
                return $person->selected === true;
            }
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
                }
            );
            if (! empty($withoutDueDate)) {
                $form->addError('Musíte vyplnit datum splatnosti');

                return;
            }
        }

        foreach ($persons as $person) {
            $ks = $person->constantSymbol ?? $values->constantSymbol;
            $ks = $ks !== null ? (int) $ks : $ks;
            $this->payments->createPayment(
                $this->groupId,
                $person->name,
                $person->email,
                (float) ($person->amount ?? $values->amount),
                $person->dueDate ?? $values->dueDate,
                (int) $person->id,
                $person->variableSymbol,
                $ks,
                $person->note
            );
        }

        $this->flashMessage('Platby byly přidány');
        $this->getPresenter()->redirect('Payment:detail', ['id' => $this->groupId]);
    }
}
