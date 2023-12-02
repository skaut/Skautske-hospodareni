<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\Forms\BaseForm;
use Cake\Chronos\ChronosDate;
use InvalidArgumentException;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\Chit\DuplicitCategory;
use Model\Cashbook\Cashbook\Chit\SingleItemRestriction;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ChitLocked;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\Commands\Cashbook\UpdateChit;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\CategoryListQuery;
use Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Common\ReadModel\Queries\MemberNamesQuery;
use Model\Common\Services\CommandBus;
use Model\Common\Services\QueryBus;
use Model\Common\UnitId;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitItem;
use Model\Skautis\Exception\AmountMustBeGreaterThanZero;
use NasExt\Forms\DependentData;
use Nette\Application\BadRequestException;
use Nette\Forms\Container;
use Nette\Forms\Control;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Http\IResponse;
use Nette\Utils\ArrayHash;
use Psr\Log\LoggerInterface;
use Skautis\Wsdl\WsdlException;

use function array_values;
use function assert;
use function sprintf;

final class ChitForm extends BaseControl
{
    private const INVALID_CHIT_NUMBER_MESSAGE = 'Číslo dokladu musí být číslo, případně číslo s prefixem až 3 velkých písmen. Pro dělené doklady můžete použít číslo za / (např. V01/1)';

    private const CATEGORY_TYPES = [
        Operation::INCOME => 'Příjmy',
        Operation::EXPENSE => 'Výdaje',
    ];

    private bool $displayChitForm = false;

    /**
     * Can current user add/edit chits?
     */
    private bool $isEditable;

    private int $itemsCount = 0;

    public function __construct(
        private CashbookId $cashbookId,
        bool $isEditable,
        private UnitId $unitId,
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private LoggerInterface $logger,
    ) {
        $this->isEditable = $isEditable;
    }

    public function isAmountValid(Control $control): bool
    {
        try {
            new Amount($control->getValue());

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function render(): void
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));

        assert($cashbook instanceof Cashbook);

        $this->template->setParameters([
            'isEditable' => $this->isEditable,
            'dataAutocomplete' => $this->getAdultMemberNames(),
            'displayChitForm' => $this->displayChitForm,
        ]);

        $this->template->setFile(__DIR__ . '/templates/ChitForm.latte');
        $this->template->render();
    }

    public function setDisplayChitForm(bool $displayChitForm): void
    {
        $this->displayChitForm = $displayChitForm;
    }

    public function setDisplayChitParent(bool $displayChitForm): void
    {
        if (! ($this->parent instanceof CashbookControl)) {
            return;
        }

        $this->parent->displayChitForm = $displayChitForm;
    }

    public function editChit(int $chitId): void
    {
        $chit = $this->queryBus->handle(new ChitQuery($this->cashbookId, $chitId));

        $this->template->setParameters(['edit' => true, 'pid' => $chit->getId(), 'num' => (string) $chit->getBody()->getNumber()]);

        if ($chit === null) {
            throw new BadRequestException(sprintf('Chit %d not found', $chitId), IResponse::S404_NotFound);
        }

        assert($chit instanceof Chit);

        if ($chit->isLocked()) {
            throw new BadRequestException('Can\'t edit locked chit', IResponse::S403_Forbidden);
        }

        $form = $this['form'];

        $form->setDefaults([
            'pid' => $chit->getId(),
            'date' => $chit->getBody()->getDate(),
            'num' => (string) $chit->getBody()->getNumber(),
            'paymentMethod' => $chit->getPaymentMethod()->toString(),
            'recipient' => (string) $chit->getBody()->getRecipient(),
            'type' => $chit->isIncome() ? Operation::INCOME : Operation::EXPENSE,
        ]);

        $items = [];
        foreach ($chit->getItems() as $item) {
            $items[] = [
                'purpose' => $item->getPurpose(),
                'price' => $item->getAmount()->getExpression(),
                $item->getCategory()->isIncome() ? 'incomeCategories' : 'expenseCategories' => $item->getCategory()->getId(),
            ];
        }

        $this['form']->setDefaults(['items' => $items]);

        $this->redrawControl();
    }

    /** @param mixed[] $values */
    public function getCategoryItems(array $values): DependentData
    {
        $type = $values['type'];

        if ($type !== null) {
            return new DependentData(
                $this->getCategoryPairsByType(Operation::get($type)),
            );
        }

        return new DependentData([
            Operation::INCOME => $this->getCategoryPairsByType(Operation::get(Operation::INCOME)),
            Operation::EXPENSE => $this->getCategoryPairsByType(Operation::get(Operation::EXPENSE)),
        ]);
    }

    protected function createComponentForm(): BaseForm
    {
        $form = new BaseForm();

        $form->addDate('date')
            ->setRequired('Zadejte datum')
            ->setHtmlAttribute('class', 'form-control input-sm required')
            ->setHtmlAttribute('placeholder', 'Datum');

        $form->addText('num')
            ->setMaxLength(5)
            ->setRequired(false)
            ->addRule($form::PATTERN, self::INVALID_CHIT_NUMBER_MESSAGE, ChitNumber::PATTERN)
            ->setHtmlAttribute('placeholder', 'Číslo dokladu')
            ->setHtmlAttribute('class', 'form-control input-sm');

        $paymentMethods = [
            PaymentMethod::CASH => 'Pokladna',
            PaymentMethod::BANK => 'Banka',
        ];

        $form->addRadioList('paymentMethod', null, $paymentMethods)
            ->setDefaultValue(PaymentMethod::CASH)
            ->setRequired(true);

        $typePicker = $form->addRadioList('type', null, self::CATEGORY_TYPES)
            ->setDefaultValue(Operation::EXPENSE)
            ->setRequired('Vyberte typ');

        $form->addText('recipient')
            ->setMaxLength(64)
            ->setHtmlId('form-recipient')
            ->setHtmlAttribute('list', 'list-recipient')
            ->setHtmlAttribute('placeholder', 'Komu/Od')
            ->setHtmlAttribute('class', 'form-control input-sm');

        $items = $form->addDynamic('items', function (Container $container) use ($typePicker): void {
            $this->itemsCount++;
            $container->addText('purpose')
                ->setMaxLength(120)
                ->setRequired('Zadejte účel výplaty')
                ->setHtmlAttribute('placeholder', 'Účel')
                ->setHtmlAttribute('class', 'form-control input-sm required');

            $container->addSelect('incomeCategories', null, $this->getCategoryPairsByType(Operation::get(Operation::INCOME)))
                ->setHtmlAttribute('class', 'form-control input-sm')
                ->setHtmlId('incomeCategories' . $this->itemsCount)
                ->addConditionOn($typePicker, Form::EQUAL, Operation::INCOME)
                ->toggle('incomeCategories' . $this->itemsCount);

            $container->addSelect('expenseCategories', null, $this->getCategoryPairsByType(Operation::get(Operation::EXPENSE)))
                ->setHtmlAttribute('class', 'form-control input-sm')
                ->setHtmlId('expenseCategories' . $this->itemsCount)
                ->addConditionOn($typePicker, Form::EQUAL, Operation::EXPENSE)
                ->toggle('expenseCategories' . $this->itemsCount);

            $container->addText('price')
                ->setRequired('Musíte vyplnit částku')
                ->addRule([$this, 'isAmountValid'], 'Částka musí být větší než 0')
                ->setMaxLength(100)
                ->setHtmlId('form-out-price')
                ->setHtmlAttribute('placeholder', 'Částka: 2+3*15')
                ->setHtmlAttribute('class', 'form-control input-sm');
            $container->addHidden('id');

            $container->addSubmit('remove', 'Odebrat položku')
                ->setValidationScope([])
                ->onClick[] = function (SubmitButton $button): void {
                    $this->removeItem($button);
                    $this->setDisplayChitForm(true);
                };
        }, 1);

        $items->addSubmit('addItem', 'Přidat další položku')
            ->setValidationScope([])
            ->onClick[] = function () use ($items): void {
                $items->createOne();
                $this->reload();
                $this->setDisplayChitForm(true);
            };

        // ID of edited chit
        $form->addHidden('pid')
            ->setRequired(false)
            ->addRule($form::INTEGER);

        $form->addSubmit('send', 'Uložit')
            ->setHtmlAttribute('class', 'btn btn-primary');

        $form->addSubmit('sendStay', 'Uložit a pokračuj')
            ->setHtmlAttribute('class', 'btn btn-secondary');

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values): void {
            if ($form->isSubmitted() !== $form['send'] && $form->isSubmitted() !== $form['sendStay']) {
                return;
            }

            $this->setDisplayChitParent(false);
            if ($form->isSubmitted() === $form['sendStay']) {
                $this->setDisplayChitParent(true);
            }

            $this->formSubmitted($form, $values);
        };

        $form->addSubmit('cancle', 'Zpět')
            ->setHtmlAttribute('class', 'btn btn-secondary')
            ->setValidationScope([])
            ->onClick[] = function (): void {
                $this->flashMessage('Úprava paragonu byla zrušena. Paragon nebyl upraven.');
                $this->setDisplayChitParent(false);
                $this->reload();
            };

        return $form;
    }

    private function removeItem(SubmitButton $button): void
    {
        $container  = $button->getParent();
        $replicator = $container->getParent();
        assert($replicator instanceof \Kdyby\Replicator\Container && $container instanceof Container);
        $replicator->remove($container, true);
        $this->reload();
    }

    private function formSubmitted(BaseForm $form, ArrayHash $values): void
    {
        if (! $this->isEditable) {
            $this->reload('Nemáte oprávnění upravovat pokladní knihu', 'danger');
        }

        $chitId        = $values['pid'] !== '' ? (int) $values['pid'] : null;
        $cashbookId    = $this->cashbookId;
        $chitBody      = $this->buildChitBodyFromValues($values);
        $method        = PaymentMethod::get($values->paymentMethod);
        $items         = [];
        $operation     = Operation::get($values->type);
        $categoriesDto = $this->queryBus->handle(new CategoryListQuery($cashbookId));

        foreach ($values->items as $item) {
            $categoryId = $operation->equals(Operation::INCOME()) ? $item->incomeCategories : $item->expenseCategories;
            $items[]    = new ChitItem(
                new Amount($item->price),
                $categoriesDto[$categoryId],
                $item->purpose,
            );
        }

        try {
            if ($chitId !== null) {
                $this->commandBus->handle(new UpdateChit($cashbookId, $chitId, $chitBody, $method, $items));
                $this->flashMessage('Paragon byl upraven.');
            } else {
                $this->commandBus->handle(new AddChitToCashbook($cashbookId, $chitBody, $method, $items));
                $this->flashMessage('Paragon byl úspěšně přidán do seznamu.');
            }

            $this->reload();
        } catch (InvalidArgumentException | CashbookNotFound $exc) {
            $this->flashMessage('Paragon se nepodařilo přidat do seznamu.', 'danger');
            $this->logger->error(sprintf('Can\'t add chit to cashbook (%s: %s)', $exc::class, $exc->getMessage()));
        } catch (ChitLocked) {
            $this->flashMessage('Nelze upravit zamčený paragon', 'error');
        } catch (AmountMustBeGreaterThanZero) {
            $form->addError('Nelze uložit doklad, protože kategorie ve skautisu nemůže být záporná!');
        } catch (WsdlException) {
            $this->flashMessage('Nepodařilo se upravit záznamy ve skautisu.', 'danger');
        } catch (DuplicitCategory) {
            $form->addError('Není dovoleno přidávat více položek se stejou kategorií!');
        } catch (SingleItemRestriction) {
            $form->addError('Převody a hromadný příjmový doklad mohou mít pouze 1 položku!');
        }
    }

    /** @return string[] */
    private function getAdultMemberNames(): array
    {
        try {
            return array_values($this->queryBus->handle(new MemberNamesQuery($this->unitId, 15)));
        } catch (WsdlException) {
            return [];
        }
    }

    /** @return string[] */
    private function getCategoryPairsByType(Operation|null $operation): array
    {
        return $this->queryBus->handle(new CategoryPairsQuery($this->cashbookId, $operation));
    }

    private function buildChitBodyFromValues(ArrayHash $values): ChitBody
    {
        $number    = $values->num !== '' ? new ChitNumber($values->num) : null;
        $recipient = $values->recipient !== '' ? new Recipient($values->recipient) : null;

        return new ChitBody($number, new ChronosDate($values->date), $recipient);
    }
}
