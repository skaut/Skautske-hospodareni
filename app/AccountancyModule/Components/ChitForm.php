<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use InvalidArgumentException;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\Category;
use Model\Cashbook\Cashbook\Chit\DuplicitCategory;
use Model\Cashbook\Cashbook\Chit\SingleItemRestriction;
use Model\Cashbook\Cashbook\ChitBody;
use Model\Cashbook\Cashbook\ChitItem;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\CashbookNotFound;
use Model\Cashbook\ChitLocked;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\Commands\Cashbook\UpdateChit;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Common\ReadModel\Queries\MemberNamesQuery;
use Model\Common\UnitId;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use NasExt\Forms\DependentData;
use Nette\Application\BadRequestException;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Forms\IControl;
use Nette\Http\IResponse;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Skautis\Wsdl\WsdlException;
use function array_values;
use function assert;
use function get_class;
use function sprintf;

final class ChitForm extends BaseControl
{
    private const INVALID_CHIT_NUMBER_MESSAGE = 'Číslo dokladu musí být číslo, případně číslo s prefixem až 3 velkých písmen. Pro dělené doklady můžete použít číslo za / (např. V01/1)';

    private const CATEGORY_TYPES = [
        Operation::INCOME => 'Příjmy',
        Operation::EXPENSE => 'Výdaje',
    ];

    /** @var CashbookId */
    private $cashbookId;

    /**
     * Can current user add/edit chits?
     *
     * @var bool
     */
    private $isEditable;

    /** @var UnitId */
    private $unitId;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $itemsCount = 0;

    public function __construct(
        CashbookId $cashbookId,
        bool $isEditable,
        UnitId $unitId,
        CommandBus $commandBus,
        QueryBus $queryBus,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->cashbookId = $cashbookId;
        $this->isEditable = $isEditable;
        $this->unitId     = $unitId;
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
        $this->logger     = $logger;
    }

    public function isAmountValid(IControl $control) : bool
    {
        try {
            new Amount($control->getValue());

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    public function render() : void
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));

        assert($cashbook instanceof Cashbook);

        $this->template->setParameters([
            'isEditable' => $this->isEditable,
            'chitNumberPrefix' => $cashbook->getChitNumberPrefix(),
        ]);

        $this->template->setFile(__DIR__ . '/templates/ChitForm.latte');
        $this->template->render();
    }

    public function editChit(int $chitId) : void
    {
        $chit = $this->queryBus->handle(new ChitQuery($this->cashbookId, $chitId));

        if ($chit === null) {
            throw new BadRequestException(sprintf('Chit %d not found', $chitId), IResponse::S404_NOT_FOUND);
        }

        assert($chit instanceof Chit);

        if ($chit->isLocked()) {
            throw new BadRequestException('Can\'t edit locked chit', IResponse::S403_FORBIDDEN);
        }

        $form = $this['form'];

        $form->setDefaults([
            'pid' => $chit->getId(),
            'date' => $chit->getBody()->getDate(),
            'num' => (string) $chit->getBody()->getNumber(),
            'paymentMethod' => $chit->getPaymentMethod()->toString(),
            'recipient' => (string) $chit->getBody()->getRecipient(),
            'type' => $chit->getCategory()->getOperationType()->getValue(),
        ]);

        $items = [];
        foreach ($chit->getItems() as $item) {
            $items[] = [
                'purpose' => $item->getPurpose(),
                'price' => $item->getAmount()->getExpression(),
                $item->getCategory()->isIncome() ? 'incomeCategories' : 'expenseCategories' => $item->getCategory()->getId(),
            ];
            $this['form']->setDefaults(['items' => $items]);
        }

        $this->redrawControl();
    }

    /**
     * @param mixed[] $values
     */
    public function getCategoryItems(array $values) : DependentData
    {
        $type = $values['type'];

        if ($type !== null) {
            return new DependentData(
                $this->getCategoryPairsByType(Operation::get($type))
            );
        }

        return new DependentData([
            Operation::INCOME => $this->getCategoryPairsByType(Operation::get(Operation::INCOME)),
            Operation::EXPENSE => $this->getCategoryPairsByType(Operation::get(Operation::EXPENSE)),
        ]);
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();

        $form->addDate('date')
            ->setRequired('Zadejte datum')
            ->setAttribute('class', 'form-control input-sm required')
            ->setAttribute('placeholder', 'Datum');

        $form->addText('num')
            ->setMaxLength(5)
            ->setRequired(false)
            ->addRule($form::PATTERN, self::INVALID_CHIT_NUMBER_MESSAGE, ChitNumber::PATTERN)
            ->setAttribute('placeholder', 'Číslo dokladu')
            ->setAttribute('class', 'form-control input-sm');

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
            ->setAttribute('data-autocomplete', Json::encode($this->getAdultMemberNames()))
            ->setAttribute('placeholder', 'Komu/Od')
            ->setAttribute('class', 'form-control input-sm');

        $removeItem = function (SubmitButton $button) : void {
            $this->removeItem($button);
        };
        $items      = $form->addDynamic('items', function (Container $container) use ($typePicker, $removeItem) : void {
            $this->itemsCount++;
            $container->addText('purpose')
                ->setMaxLength(120)
                ->setRequired('Zadejte účel výplaty')
                ->setAttribute('placeholder', 'Účel')
                ->setAttribute('class', 'form-control input-sm required');

            $container->addSelect('incomeCategories', null, $this->getCategoryPairsByType(Operation::get(Operation::INCOME)))
                ->setAttribute('class', 'form-control input-sm')
                ->addConditionOn($typePicker, Form::EQUAL, Operation::INCOME)
                ->toggle('incomeCategories' . $this->itemsCount);

            $container->addSelect('expenseCategories', null, $this->getCategoryPairsByType(Operation::get(Operation::EXPENSE)))
                ->setAttribute('class', 'form-control input-sm')
                ->addConditionOn($typePicker, Form::EQUAL, Operation::EXPENSE)
                ->toggle('expenseCategories' . $this->itemsCount);

            $container->addText('price')
                ->setRequired('Musíte vyplnit částku')
                ->addRule([$this, 'isAmountValid'], 'Částka musí být větší než 0')
                ->setMaxLength(100)
                ->setHtmlId('form-out-price')
                ->setAttribute('placeholder', 'Částka: 2+3*15')
                ->setAttribute('class', 'form-control input-sm');
            $container->addHidden('id');

            $container->addSubmit('remove', 'Odebrat položku')
                ->setValidationScope(false) // disables validation
                ->onClick[] = $removeItem;
        }, 1);

        $items->addSubmit('addItem', 'Přidat další položku')
            ->setValidationScope(false)
            ->onClick[] = function () use ($items) : void {
                $items->createOne();
                $this->redrawControl();
            };

        // ID of edited chit
        $form->addHidden('pid')
            ->setRequired(false)
            ->addRule($form::INTEGER);

        $form->addSubmit('send', 'Uložit')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values) : void {
            if ($form->isSubmitted() !== $form['send']) {
                return;
            }
            $this->formSubmitted($form, $values);
            $this->redirect('this');
        };

        return $form;
    }

    private function removeItem(SubmitButton $button) : void
    {
        $container  = $button->getParent();
        $replicator = $container->getParent();
        assert($replicator instanceof \Kdyby\Replicator\Container && $container instanceof Container);
        $replicator->remove($container, true);
        $this->redrawControl();
    }

    private function formSubmitted(BaseForm $form, ArrayHash $values) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat pokladní knihu', 'danger');
            $this->redirect('this');
        }

        $chitId     = $values['pid'] !== '' ? (int) $values['pid'] : null;
        $cashbookId = $this->cashbookId;
        $chitBody   = $this->buildChitBodyFromValues($values);
        $method     = PaymentMethod::get($values->paymentMethod);
        $items      = [];
        $operation  = Operation::get($values->type);

        foreach ($values->items as $item) {
            $categoryId = $operation->equals(Operation::INCOME()) ? $item->incomeCategories : $item->expenseCategories;
            $items[]    = new ChitItem(
                new Amount($item->price),
                new Category($categoryId, $operation),
                $item->purpose
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
        } catch (InvalidArgumentException | CashbookNotFound $exc) {
            $this->flashMessage('Paragon se nepodařilo přidat do seznamu.', 'danger');
            $this->logger->error(sprintf('Can\'t add chit to cashbook (%s: %s)', get_class($exc), $exc->getMessage()));
        } catch (ChitLocked $e) {
            $this->flashMessage('Nelze upravit zamčený paragon', 'error');
        } catch (WsdlException $se) {
            $this->flashMessage('Nepodařilo se upravit záznamy ve skautisu.', 'danger');
        } catch (DuplicitCategory $e) {
            $this->flashMessage('Není dovolneo přidávat více položek se stejou kategorií!', 'danger');
        } catch (SingleItemRestriction $e) {
            $this->flashMessage('Převody a hromadný příjmový doklad mohou mít pouze 1 položku!', 'danger');
        }
    }

    /**
     * @return string[]
     */
    private function getAdultMemberNames() : array
    {
        try {
            return array_values($this->queryBus->handle(new MemberNamesQuery($this->unitId, 15)));
        } catch (WsdlException $e) {
            return [];
        }
    }

    /**
     * @return string[]
     */
    private function getCategoryPairsByType(?Operation $operation) : array
    {
        return $this->queryBus->handle(new CategoryPairsQuery($this->cashbookId, $operation));
    }

    private function buildChitBodyFromValues(ArrayHash $values) : ChitBody
    {
        $number    = $values->num !== '' ? new ChitNumber($values->num) : null;
        $recipient = $values->recipient !== '' ? new Recipient($values->recipient) : null;

        return new ChitBody($number, $values->date, $recipient);
    }
}
