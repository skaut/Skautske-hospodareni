<?php

declare(strict_types=1);

namespace Component\Forms;

use InvalidArgumentException;
use LogicException;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\Utils\Html;
use Nette\Utils\Validators;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function trim;

/**
 * E-mail recipients as a list of addresses, so nobody types separators: the offered addresses (e.g. the
 * contacts skautIS has for a member) are rendered as checkboxes, selected addresses outside the offer get
 * the fallback label, and any other address is typed into the input below the list. The page
 * (frontend/ts/emailList.ts) turns a typed address into a checked row; without it the typed address is
 * still taken on submit. The value is the list of checked addresses.
 */
final class EmailListControl extends BaseControl
{
    /** @var array<string, string> address => label of its source */
    private array $offered = [];

    /** @var list<string> */
    private array $addresses = [];

    public function __construct(string $label, private string $fallbackLabel)
    {
        parent::__construct($label);
    }

    /** @param array<string, string> $offered address => label of its source */
    public function setOffered(array $offered): self
    {
        $this->offered = $offered;

        return $this;
    }

    /** @return array<string, string> address => label of its source */
    public function getOffered(): array
    {
        return $this->offered;
    }

    public function loadHttpData(): void
    {
        $selected = $this->getHttpData(Form::DATA_TEXT, '[selected][]');
        $typed = $this->getHttpData(Form::DATA_LINE, '[new]');

        $addresses = is_array($selected) ? $selected : [];
        if (is_string($typed) && $typed !== '') {
            $addresses[] = $typed;
        }

        $this->setValue($addresses);
    }

    public function setValue(mixed $value): static
    {
        if ($value === null) {
            $value = [];
        } elseif (! is_array($value)) {
            throw new InvalidArgumentException(sprintf("Value must be array or null, %s given in field '%s'.", get_debug_type($value), $this->getName()));
        }

        $this->addresses = array_values(array_unique(array_filter(
            array_map(static fn (string $address): string => trim($address), $value),
            static fn (string $address): bool => $address !== '',
        )));

        return $this;
    }

    /** @return list<string> */
    public function getValue(): array
    {
        return $this->addresses;
    }

    public function validate(): void
    {
        parent::validate();

        foreach ($this->addresses as $address) {
            if (! Validators::isEmail($address)) {
                $this->addError(sprintf("E-mail '%s' nemá platný formát.", $address));
            }
        }
    }

    public function getLabel(mixed $caption = null): Html
    {
        $label = parent::getLabel($caption);
        if (! $label instanceof Html) {
            throw new LogicException('Assertion failed.');
        }

        return $label->for(null);
    }

    public function getControl(): Html
    {
        $this->setOption('rendered', true);

        $items = Html::el('div', ['data-email-list-items' => true]);
        $index = 0;
        foreach ($this->rows() as $address => $label) {
            $id = $this->getHtmlId().'-'.$index++;
            $items->addHtml(
                Html::el('div', ['class' => 'form-check', 'data-test' => 'email-list-item'])
                    ->addHtml(Html::el('input', [
                        'type' => 'checkbox',
                        'class' => 'form-check-input',
                        'name' => $this->getHtmlName().'[selected][]',
                        'id' => $id,
                        'value' => $address,
                        'checked' => in_array($address, $this->addresses, true),
                        'disabled' => $this->isDisabled(),
                    ]))
                    ->addHtml(
                        Html::el('label', ['class' => 'form-check-label', 'for' => $id])
                            ->addText($address.' ')
                            ->addHtml(Html::el('span', ['class' => 'text-body-secondary'])->setText('('.$label.')')),
                    ),
            );
        }

        $typed = Html::el('input', [
            'type' => 'email',
            'class' => 'form-control',
            'name' => $this->getHtmlName().'[new]',
            'id' => $this->getHtmlId().'-new',
            'placeholder' => 'další e-mail',
            'autocomplete' => 'off',
            'disabled' => $this->isDisabled(),
            'data-email-list-input' => true,
        ]);
        $add = Html::el('button', [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary',
            'disabled' => $this->isDisabled(),
            'data-email-list-add' => true,
            'data-test' => 'email-list-add',
        ])->setText('Přidat');

        return Html::el('div', [
            'data-email-list' => true,
            'data-email-list-name' => $this->getHtmlName().'[selected][]',
            'data-email-list-id' => $this->getHtmlId(),
            'data-email-list-fallback-label' => $this->fallbackLabel,
        ])
            ->addHtml($items)
            ->addHtml(Html::el('div', ['class' => 'input-group mt-1'])->addHtml($typed)->addHtml($add));
    }

    /** @return array<string, string> every address to show (offered first) with the label of its source */
    private function rows(): array
    {
        $rows = $this->offered;
        foreach ($this->addresses as $address) {
            $rows[$address] ??= $this->fallbackLabel;
        }

        return $rows;
    }
}
