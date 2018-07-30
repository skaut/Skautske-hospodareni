<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use InvalidArgumentException;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\CashbookId;
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
use Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\MemberService;
use NasExt\Forms\DependentData;
use Nette\Application\BadRequestException;
use Nette\Forms\IControl;
use Nette\Http\IResponse;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use Skautis\Wsdl\WsdlException;
use function array_values;
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

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    /** @var MemberService */
    private $memberService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        CashbookId $cashbookId,
        bool $isEditable,
        CommandBus $commandBus,
        QueryBus $queryBus,
        MemberService $memberService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->cashbookId    = $cashbookId;
        $this->isEditable    = $isEditable;
        $this->commandBus    = $commandBus;
        $this->queryBus      = $queryBus;
        $this->memberService = $memberService;
        $this->logger        = $logger;
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
        /**
 * @var Cashbook $cashbook
*/
        $cashbook = $this->queryBus->handle(new CashbookQuery($this->cashbookId));
        $this->template->setParameters(
            [
            'isEditable' => $this->isEditable,
            'chitNumberPrefix' => $cashbook->getChitNumberPrefix(),
            ]
        );

        $this->template->setFile(__DIR__ . '/templates/ChitForm.latte');
        $this->template->render();
    }

    public function editChit(int $chitId) : void
    {
        /**
 * @var Chit|NULL $chit
*/
        $chit = $this->queryBus->handle(new ChitQuery($this->cashbookId, $chitId));

        if ($chit === null) {
            throw new BadRequestException(sprintf('Chit %d not found', $chitId), IResponse::S404_NOT_FOUND);
        }

        if ($chit->isLocked()) {
            throw new BadRequestException('Can\'t edit locked chit', IResponse::S403_FORBIDDEN);
        }

        /**
 * @var BaseForm $form
*/
        $form = $this['form'];

        $form['category']->setItems($this->getCategoryPairsByType($chit->getCategory()->getOperationType()));

        $form->setDefaults(
            [
            'pid' => $chit->getId(),
            'date' => $chit->getDate(),
            'num' => (string) $chit->getNumber(),
            'recipient' => (string) $chit->getRecipient(),
            'purpose' => $chit->getPurpose(),
            'price' => $chit->getAmount()->getExpression(),
            'type' => $chit->getCategory()->getOperationType()->getValue(),
            'category' => $chit->getCategory()->getId(),
            ]
        );

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

        return new DependentData(
            [
            Operation::INCOME => $this->getCategoryPairsByType(Operation::get(Operation::INCOME)),
            Operation::EXPENSE => $this->getCategoryPairsByType(Operation::get(Operation::EXPENSE)),
            ]
        );
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
            ->setAttribute('placeholder', 'Číslo')
            ->setAttribute('class', 'form-control input-sm');

        $paymentMethods = [
            PaymentMethod::CASH => 'Pokladna',
            PaymentMethod::BANK => 'Banka',
        ];

        $form->addSelect('paymentMethod', null, $paymentMethods)
            ->setRequired(true)
            ->setAttribute('class', 'form-control input-sm required');

        $form->addText('purpose')
            ->setMaxLength(120)
            ->setRequired('Zadejte účel výplaty')
            ->setAttribute('placeholder', 'Účel')
            ->setAttribute('class', 'form-control input-sm required');

        $form->addSelect('type', null, self::CATEGORY_TYPES)
            ->setAttribute('size', '2')
            ->setDefaultValue(Operation::EXPENSE)
            ->setRequired('Vyberte typ');

        $form->addDependentSelectBox('category', null, $form['type'])
            ->setDependentCallback([$this, 'getCategoryItems'])
            ->setAttribute('class', 'form-control input-sm');

        $form->addText('recipient')
            ->setMaxLength(64)
            ->setHtmlId('form-recipient')
            ->setAttribute('data-autocomplete', Json::encode($this->getAdultMemberNames()))
            ->setAttribute('placeholder', 'Komu/Od')
            ->setAttribute('class', 'form-control input-sm');

        $form->addText('price')
            ->setRequired('Musíte vyplnit částku')
            ->addRule([$this, 'isAmountValid'], 'Částka musí být větší než 0')
            ->setMaxLength(100)
            ->setHtmlId('form-out-price')
            ->setAttribute('placeholder', 'Částka: 2+3*15')
            ->setAttribute('class', 'form-control input-sm');

        // ID of edited chit
        $form->addHidden('pid')
            ->setRequired(false)
            ->addRule($form::INTEGER);

        $form->addSubmit('send', 'Uložit')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values) : void {
            $this->formSubmitted($form, $values);
            $this->redirect('this');
        };

        return $form;
    }

    private function formSubmitted(BaseForm $form, ArrayHash $values) : void
    {
        if (! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat pokladní knihu', 'danger');
            $this->redirect('this');
        }

        $chitId     = $values['pid'] !== '' ? (int) $values['pid'] : null;
        $cashbookId = $this->cashbookId;
        $category   = $values->category;
        $chitBody   = $this->buildChitBodyFromValues($values);
        $method     = PaymentMethod::get($values->paymentMethod);

        try {
            if ($chitId !== null) {
                $this->commandBus->handle(new UpdateChit($cashbookId, $chitId, $chitBody, $category, $method));
                $this->flashMessage('Paragon byl upraven.');
            } else {
                $this->commandBus->handle(new AddChitToCashbook($cashbookId, $chitBody, $category, $method));
                $this->flashMessage('Paragon byl úspěšně přidán do seznamu.');
            }
        } catch (InvalidArgumentException | CashbookNotFound $exc) {
            $this->flashMessage('Paragon se nepodařilo přidat do seznamu.', 'danger');
            $this->logger->error(sprintf('Can\'t add chit to cashbook (%s: %s)', get_class($exc), $exc->getMessage()));
        } catch (ChitLocked $e) {
            $this->flashMessage('Nelze upravit zamčený paragon', 'error');
        } catch (WsdlException $se) {
            $this->flashMessage('Nepodařilo se upravit záznamy ve skautisu.', 'danger');
        }
    }

    /**
     * @return string[]
     */
    private function getAdultMemberNames() : array
    {
        try {
            return array_values($this->memberService->getCombobox(false, 15));
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

        return new ChitBody($number, $values->date, $recipient, new Amount($values->price), $values->purpose);
    }
}
