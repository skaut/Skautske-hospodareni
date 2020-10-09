<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Camp as CampResource;
use Model\Auth\Resources\Event as EventResource;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Commands\Cashbook\MoveChitsToDifferentCashbook;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\Camp;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\CampListQuery;
use Model\Event\ReadModel\Queries\EventListQuery;
use Nette\Utils\ArrayHash;
use function array_map;
use function assert;
use function explode;
use function implode;
use function in_array;

class MoveChitsDialog extends BaseControl
{
    /**
     * Comma-separated chit IDS (because persistent parameters don't support arrays)
     *
     * @var        string
     * @persistent
     */
    public $chitIds;

    /**
     * @var bool
     * @persistent
     */
    public $opened = false;

    private CashbookId $cashbookId;

    private CommandBus $commandBus;

    private QueryBus $queryBus;

    private IAuthorizator $authorizator;

    public function __construct(
        CashbookId $cashbookId,
        CommandBus $commandBus,
        QueryBus $queryBus,
        IAuthorizator $authorizator
    ) {
        parent::__construct();
        $this->cashbookId   = $cashbookId;
        $this->commandBus   = $commandBus;
        $this->queryBus     = $queryBus;
        $this->authorizator = $authorizator;
    }

    /**
     * @param int[] $chitIds
     */
    public function open(array $chitIds) : void
    {
        $this->chitIds = implode(',', $chitIds);
        $this->opened  = true;
        $this->redrawControl();
    }

    public function render() : void
    {
        $this->template->setParameters(['renderModal' => $this->opened]);

        $this->template->setFile(__DIR__ . '/templates/MoveChitsDialog.latte');
        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form  = new BaseForm();
        $items = [
            'Výpravy' => $this->getEventCashbooks(),
            'Tábory' => $this->getCampCashbooks(),
        ];

        $form->addSelect('newCashbookId', 'Nová evidence plateb:', $items)
            ->setPrompt('Zvolte knihu');

        $form->addSubmit('move', 'Přesunout doklady')
            ->setAttribute('class', 'ajax');

        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values) : void {
            $this->formSubmitted($form, $values);
        };

        return $form;
    }

    private function formSubmitted(BaseForm $form, ArrayHash $values) : void
    {
        $chitIds = $this->getChitIds();

        if (empty($chitIds)) {
            $form->addError('Nebyly vybrány žádné paragony!');

            return;
        }

        if ($values->newCashbookId === null) {
            $form->addError('Nebyla vybrána žádná cílová evidence plateb!');

            return;
        }

        $newCashbookId = CashbookId::fromString((string) $values->newCashbookId);

        if (! $this->canEdit($this->cashbookId) || ! $this->canEdit($newCashbookId)) {
            $this->flashMessage('Nemáte oprávnění k původní nebo nové pokladní knize!', 'danger');
            $this->redirect('this');
        }

        $this->commandBus->handle(
            new MoveChitsToDifferentCashbook($chitIds, $this->cashbookId, $newCashbookId)
        );

        $this->redirect('this');
    }

    /**
     * @return int[]
     */
    private function getChitIds() : array
    {
        $chitIds = explode(',', $this->chitIds);

        return array_map('\intval', $chitIds);
    }

    /**
     * @return array<string, string> Cashbook ID => Camp name
     */
    private function getCampCashbooks() : array
    {
        $cashbooks = [];

        foreach ($this->queryBus->handle(new CampListQuery(null)) as $camp) {
            assert($camp instanceof Camp);

            if (! in_array($camp->getState(), ['draft', 'approvedParent', 'approvedLeader'], true)) {
                continue;
            }

            $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($camp->getId()));

            assert($cashbookId instanceof CashbookId);

            $cashbooks[$cashbookId->toString()] = $camp->getDisplayName();
        }

        return $cashbooks;
    }

    /**
     * @return array<string, string> Cashbook ID => Event name
     */
    private function getEventCashbooks() : array
    {
        $cashbooks = [];

        foreach ($this->queryBus->handle(new EventListQuery(null)) as $event) {
            assert($event instanceof Event);

            if ($event->getState() !== 'draft') {
                continue;
            }

            $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery($event->getId()));

            assert($cashbookId instanceof CashbookId);

            $cashbooks[$cashbookId->toString()] = $event->getDisplayName();
        }

        return $cashbooks;
    }

    private function canEdit(CashbookId $cashbookId) : bool
    {
        $type      = $this->getCashbookType($cashbookId);
        $skautisId = $this->getSkautisId($cashbookId);

        if ($type->equalsValue(CashbookType::EVENT)) {
            return $this->authorizator->isAllowed(EventResource::UPDATE, $skautisId);
        }

        return $this->authorizator->isAllowed(CampResource::UPDATE, $skautisId)
            || $this->authorizator->isAllowed(CampResource::UPDATE_REAL, $skautisId)
            || $this->authorizator->isAllowed(CampResource::UPDATE_REAL_COST, $skautisId);
    }

    private function getCashbookType(CashbookId $cashbookId) : CashbookType
    {
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));

        assert($cashbook instanceof Cashbook);

        return $cashbook->getType();
    }

    private function getSkautisId(CashbookId $cashbookId) : int
    {
        return $this->queryBus->handle(new SkautisIdQuery($cashbookId));
    }
}
