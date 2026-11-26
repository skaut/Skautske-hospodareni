<?php

declare(strict_types=1);

namespace App\Components\Payment;

use App\Components\BaseControl;
use App\Model\Common\EmailAddress;
use App\Model\Common\Services\CommandBus;
use App\Model\DTO\Payment\MemberEmail;
use App\Model\DTO\Payment\MemberEmailType;
use App\Model\Payment\Commands\Payment\CreatePayment;
use App\Model\Payment\PaymentService;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Services\VariableSymbolCollisionChecker;
use App\Model\Payment\VariableSymbol;
use App\Model\Payment\VariableSymbolCollision;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Component\Forms\BaseContainer;
use Component\Forms\BaseForm;
use LogicException;
use Nette\Forms\Controls\BaseControl as FormControl;
use Nette\Forms\Controls\TextBase;
use RuntimeException;

use function array_filter;
use function array_map;
use function array_values;
use function sprintf;

class MassAddForm extends BaseControl
{
    public function __construct(
        private int $groupId,
        private PaymentService $payments,
        private CommandBus $commandBus,
        private IGroupRepository $groups,
        private VariableSymbolCollisionChecker $variableSymbols,
    ) {
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $form->getElementPrototype()->setAttribute('data-mass-add-form', '');
        $form->getElementPrototype()->setAttribute('novalidate', 'novalidate');

        $form->addText('amount', 'Částka:')
            ->setNullable()
            ->setRequired(false)
            ->addRule($form::FLOAT, 'Částka musí být číslo')
            ->addRule($form::PATTERN, PaymentFormFields::MONEY_PATTERN_MESSAGE, PaymentFormFields::MONEY_PATTERN)
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
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formSubmitted($form);
        };

        $group = $this->payments->getGroup($this->groupId) ?? throw new RuntimeException('Platební skupina nebyla nalezena.');

        $form->setDefaults([
            'amount' => $group->getDefaultAmount() === null ? null : MoneyFactory::toDecimal($group->getDefaultAmount()),
            'dueDate' => $group->getDueDate(),
            'constantSymbol' => $group->getConstantSymbol(),
        ]);

        return $form;
    }

    /** @param MemberEmail[] $emails */
    public function addPerson(int $id, array $emails, string $name, ?\Money\Money $amount = null, string $note = '', string $variableSymbol = '', ?ChronosDate $dueDate = null): void
    {
        $form = $this['form'];
        $persons = $form['persons'];
        $defaultAmount = $form['amount'];

        if (! ($defaultAmount instanceof TextBase && $persons instanceof BaseContainer)) {
            throw new LogicException('Assertion failed.');
        }
        $container = $persons->addContainer('person'.$id);

        $selected = $container->addCheckbox('selected')
            ->setHtmlAttribute('data-mass-add-person-selection', '');

        $emailItems = [];
        $emailTypes = [];
        $defaultEmails = [];
        foreach ($emails as $email) {
            $address = $email->getAddress();
            $emailItems[$address] = $email->getLabel();
            $emailTypes[$address] = $email->getType()->value;
            if ($email->getType()->isSelectedByDefault()) {
                $defaultEmails[] = $address;
            }
        }

        $container->addMultiSelect('email', null, $emailItems)
            ->setOptionAttribute('data-email-type:', $emailTypes)
            ->setHtmlAttribute('data-mass-email-select', '1')
            ->setDefaultValue($defaultEmails)
            ->setRequired(false);

        $container->addText('name')
            ->setRequired('Musíte vyplnit jméno')
            ->setDefaultValue($name);

        $container->addHidden('id', $id);

        $container->addText('amount', 'Částka:')
            ->setHtmlAttribute('class', 'input-mini')
            ->setHtmlType('number')
            ->setHtmlAttribute('step', '0.01')
            ->setRequired(false)
            ->setNullable()
            ->setDefaultValue($amount === null ? null : MoneyFactory::toDecimal($amount))
            ->addConditionOn($selected, $form::FILLED)
            ->addRule($form::FLOAT, 'Částka musí být číslo')
            ->addRule($form::PATTERN, PaymentFormFields::MONEY_PATTERN_MESSAGE, PaymentFormFields::MONEY_PATTERN)
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
        $this->template->setParameters([
            'backLink' => $this->getPresenter()->link(':Payments:Payment:default', ['id' => $this->groupId]),
            'emailTypes' => array_values(array_filter(
                MemberEmailType::cases(),
                static fn (MemberEmailType $type): bool => $type->isBulkSelectable(),
            )),
        ]);
        $this->template->setFile(__DIR__.'/templates/MassAddForm.latte');
        $this->template->render();
    }

    private function formSubmitted(BaseForm $form): void
    {
        $values = $form->getValues(\Nette\Utils\ArrayHash::class);

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

        if ($this->addVariableSymbolCollisions($form, $persons)) {
            return;
        }

        $added = 0;

        foreach ($persons as $person) {
            try {
                $this->commandBus->handle(
                    new CreatePayment(
                        $this->groupId,
                        $person->name,
                        array_map(fn (string $email) => new EmailAddress($email), $person->email),
                        MoneyFactory::fromDecimal((string) ($person->amount ?? $values->amount)),
                        new ChronosDate($person->dueDate ?? $values->dueDate),
                        (int) $person->id,
                        $person->variableSymbol,
                        $person->constantSymbol ?? $values->constantSymbol,
                        $person->note,
                    ),
                );
            } catch (VariableSymbolCollision $collision) {
                // The symbol was taken meanwhile by someone else; the payments before it are already added.
                $form->addError($collision->getMessage().($added === 0 ? ' Žádná platba nebyla přidána.' : ' Platby před ní už přidané jsou, zkontrolujte seznam plateb skupiny.'));

                return;
            }

            ++$added;
        }

        $this->flashMessage('Platby byly přidány');
        $this->getPresenter()->redirect(':Payments:Payment:default', ['id' => $this->groupId]);
    }

    /**
     * Checks the variable symbols of all selected people before the first payment is added, so a taken symbol
     * never leaves just a part of them added. Marks each colliding symbol at its person.
     *
     * @param array<string, mixed> $persons selected people by the name of their container
     */
    private function addVariableSymbolCollisions(BaseForm $form, array $persons): bool
    {
        $variableSymbols = [];

        foreach ($persons as $key => $person) {
            $variableSymbol = $person->variableSymbol ?? null;

            if ($variableSymbol instanceof VariableSymbol) {
                $variableSymbols[$key] = $variableSymbol;
            }
        }

        $collisions = $this->variableSymbols->findCollisionsOfNewPayments($this->groups->find($this->groupId), $variableSymbols);

        foreach ($collisions as $key => $collision) {
            $field = $form->getComponent(sprintf('persons-%s-variableSymbol', $key), false);

            if ($field instanceof FormControl) {
                $field->addError($collision->getMessage());
            } else {
                $form->addError($collision->getMessage());
            }
        }

        if ($collisions !== []) {
            $form->addError('Žádná platba nebyla přidána. Opravte variabilní symboly a přidejte platby znovu.');
        }

        return $collisions !== [];
    }
}
