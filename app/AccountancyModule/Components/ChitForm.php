<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components;

use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use InvalidArgumentException;
use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Cashbook\ChitNumber;
use Model\Cashbook\Cashbook\Recipient;
use Model\Cashbook\CashbookNotFoundException;
use Model\Cashbook\ChitLockedException;
use Model\Cashbook\Commands\Cashbook\AddChitToCashbook;
use Model\Cashbook\Commands\Cashbook\UpdateChit;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\Queries\CashbookNumberPrefixQuery;
use Model\Cashbook\ReadModel\Queries\CategoryPairsQuery;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\DTO\Cashbook\Chit;
use Model\MemberService;
use Nette\Application\BadRequestException;
use Nette\Forms\IControl;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Psr\Log\LoggerInterface;
use function get_class;

final class ChitForm extends BaseControl
{

    private const INVALID_CHIT_NUMBER_MESSAGE = 'Číslo dokladu musí být číslo, případně číslo s prefixem až 3 velkých písmen. Pro dělené doklady můžete použít číslo za / (např. V01/1)';

    private const CATEGORY_TYPES = [
        Operation::INCOME   => 'Příjmy',
        Operation::EXPENSE  => 'Výdaje',
    ];

    /** @var int */
    private $cashbookId;

    /**
     * Can current user add/edit chits?
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
        int $cashbookId,
        bool $isEditable,
        CommandBus $commandBus,
        QueryBus $queryBus,
        MemberService $memberService,
        LoggerInterface $logger
    )
    {
        parent::__construct();
        $this->cashbookId = $cashbookId;
        $this->isEditable = $isEditable;
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
        $this->memberService = $memberService;
        $this->logger = $logger;
    }

    public function isAmountValid(IControl $control): bool
    {
        try {
            new Amount($control->getValue());

            return TRUE;
        } catch (InvalidArgumentException $e) {
            return FALSE;
        }
    }

    public function render(): void
    {
        $this->template->setParameters([
            'isEditable'        => $this->isEditable,
            'chitNumberPrefix'  => $this->queryBus->handle(new CashbookNumberPrefixQuery($this->cashbookId)),
        ]);

        $this->template->setFile(__DIR__ . '/templates/ChitForm.latte');
        $this->template->render();
    }

    public function editChit(int $chitId): void
    {
        $form = $this['cashbookForm'];

        /** @var Chit|NULL $chit */
        $chit = $this->queryBus->handle(new ChitQuery($this->cashbookId, $chitId));

        if ($chit === NULL) {
            throw new BadRequestException(sprintf('Chit %d not found', $chitId), IResponse::S404_NOT_FOUND);
        }

        if ($chit->isLocked()) {
            throw new BadRequestException('Can\'t edit locked chit', IResponse::S403_FORBIDDEN);
        }

        $form['category']->setItems($this->getCategoryPairsByType($chit->getCategory()->getOperationType()));

        $form->setDefaults([
            'pid'       => $chit->getId(),
            'date'      => $chit->getDate(),
            'num'       => (string) $chit->getNumber(),
            'recipient' => (string) $chit->getRecipient(),
            'purpose'   => $chit->getPurpose(),
            'price' => $chit->getAmount()->getExpression(),
            'type' => $chit->getCategory()->getOperationType()->getValue(),
            'category' => $chit->getCategory()->getId(),
        ]);

        $this->redrawControl();
    }

    /**
     * @param BaseForm $form
     * @return array<int,string>|array<string,array<int,string>>
     */
    public function getTypeItems(BaseForm $form): array
    {
        $type = $form['type']->getValue();

        if ($type === NULL) {
            return [
                Operation::INCOME => $this->getCategoryPairsByType(Operation::get(Operation::INCOME)),
                Operation::EXPENSE => $this->getCategoryPairsByType(Operation::get(Operation::EXPENSE)),
            ];
        }

        return $this->getCategoryPairsByType(NULL);
    }

    protected function createComponentForm(string $name): BaseForm
    {
        $form = new BaseForm();
        $this->addComponent($form, $name); // necessary for JSelect

        $form->addDate('date')
            ->setRequired('Zadejte datum')
            ->setAttribute('class', 'form-control input-sm required')
            ->setAttribute('placeholder', 'Datum');

        $form->addText('num')
            ->setMaxLength(5)
            ->setRequired(FALSE)
            ->addRule($form::PATTERN, self::INVALID_CHIT_NUMBER_MESSAGE, ChitNumber::PATTERN)
            ->setAttribute('placeholder', 'Číslo')
            ->setAttribute('class', 'form-control input-sm');

        $form->addText('purpose')
            ->setMaxLength(120)
            ->setRequired('Zadejte účel výplaty')
            ->setAttribute('placeholder', 'Účel')
            ->setAttribute('class', 'form-control input-sm required');

        $form->addSelect('type', NULL, self::CATEGORY_TYPES)
            ->setAttribute('size', '2')
            ->setDefaultValue(Operation::EXPENSE)
            ->setRequired('Vyberte typ');

        $form->addJSelect('category', NULL, $form['type'], [$this, 'getTypeItems'])
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
            ->setRequired(FALSE)
            ->addRule($form::INTEGER);

        $form->addSubmit('send', 'Uložit')
            ->setAttribute('class', 'btn btn-primary');

        $form->onSuccess[] = function (BaseForm $form): void {
            $this->formSubmitted($form);
            $this->redirect('this');
        };

        return $form;
    }

    private function formSubmitted(BaseForm $form): void
    {
        if ( ! $this->isEditable) {
            $this->flashMessage('Nemáte oprávnění upravovat pokladní knihu', 'danger');
            $this->redirect('this');
        }

        $values = $form->getValues();

        $chitId = $values['pid'] !== '' ? (int) $values['pid'] : NULL;
        $number = $values->num !== '' ? new ChitNumber($values->num) : NULL;

        $date = $values->date;

        $cashbookId = $this->cashbookId;
        $recipient = $values->recipient !== '' ? new Recipient($values->recipient) : NULL;
        $amount = new Amount($values->price);
        $purpose = $values->purpose;
        $category = $values->category;

        try {
            /*
             * Update existing chit
             */
            if ($chitId !== NULL) {
                $this->commandBus->handle(
                    new UpdateChit($cashbookId, $chitId, $number, $date, $recipient, $amount, $purpose, $category)
                );
                $this->flashMessage('Paragon byl upraven.');
                return;
            }

            /*
             * Add new chit
             */
            $this->commandBus->handle(
                new AddChitToCashbook($cashbookId, $number, $date, $recipient, $amount, $purpose, $category)
            );
            $this->flashMessage('Paragon byl úspěšně přidán do seznamu.');
        } catch (InvalidArgumentException | CashbookNotFoundException $exc) {
            $this->flashMessage('Paragon se nepodařilo přidat do seznamu.', 'danger');
            $this->logger->error(sprintf('Can\'t add chit to cashbook (%s: %s)', get_class($exc), $exc->getMessage()));
        } catch (ChitLockedException $e) {
            $this->flashMessage('Nelze upravit zamčený paragon', 'error');
        } catch (\Skautis\Wsdl\WsdlException $se) {
            $this->flashMessage('Nepodařilo se upravit záznamy ve skautisu.', 'danger');
        }
    }

    /**
     * @return string[]
     */
    private function getAdultMemberNames(): array
    {
        try {
            return array_values($this->memberService->getCombobox(FALSE, 15));
        } catch (\Skautis\Wsdl\WsdlException $e) {
            return [];
        }
    }

    /**
     * @return array<int,string>
     */
    private function getCategoryPairsByType(?Operation $operation): array
    {
        return $this->queryBus->handle(new CategoryPairsQuery($this->cashbookId, $operation));
    }

}
