<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Camp;
use Model\Auth\Resources\Event;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Commands\Cashbook\MoveChitsToDifferentCashbook;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\DTO\Cashbook\Cashbook;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\EventEntity;
use Nette\DI\Container;
use Nette\Utils\ArrayHash;

class MoveChitsDialog extends BaseControl
{

    /**
     * Comma-separated chit IDS (because persistent parameters don't support arrays)
     * @var string
     * @persistent
     */
    public $chitIds;

    /**
     * @var bool
     * @persistent
     */
    public $opened = FALSE;

    /** @var CashbookId */
    private $cashbookId;

    /** @var CommandBus */
    private $commandBus;

    /** @var QueryBus */
    private $queryBus;

    /** @var IAuthorizator */
    private $authorizator;

    /** @var Container */
    private $context;

    public function __construct(CashbookId $cashbookId, CommandBus $commandBus, QueryBus $queryBus, IAuthorizator $authorizator, Container $context)
    {
        parent::__construct();
        $this->cashbookId = $cashbookId;
        $this->commandBus = $commandBus;
        $this->queryBus = $queryBus;
        $this->authorizator = $authorizator;
        $this->context = $context;
    }

    /**
     * @param int[] $chitIds
     */
    public function open(array $chitIds) : void
    {
        $this->chitIds = implode(',', $chitIds);
        $this->opened = TRUE;
        $this->redrawControl();
    }

    public function render() : void
    {
        $this->template->setParameters([
            'renderModal' => $this->opened,
        ]);

        $this->template->setFile(__DIR__ . '/templates/MoveChitsDialog.latte');
        $this->template->render();
    }

    protected function createComponentForm() : BaseForm
    {
        $form = new BaseForm();
        $items = [
            'Výpravy' => $this->getListOfEvents(ObjectType::EVENT, ['draft']),
            'Tábory' => $this->getListOfEvents(ObjectType::CAMP, ['draft', 'approvedParent', 'approvedLeader']),
        ];

        $form->addSelect('newCashbookId', 'Nová pokladní kniha:', $items)
            ->setPrompt('Zvolte knihu');

        $form->addSubmit('move', 'Přesunout doklady')
            ->setAttribute('class', 'ajax');


        $form->onSuccess[] = function(BaseForm $form, ArrayHash $values) : void {
            $this->formSubmitted($form, $values);
        };

        return $form;
    }

    private function formSubmitted(BaseForm $form, ArrayHash $values) : void
    {
        $chitIds = $this->getChitIds();

        if(empty($chitIds)) {
            $form->addError('Nebyly vybrány žádné paragony!');
            return;
        }


        if($values->newCashbookId === NULL) {
            $form->addError('Nebyla vybrána žádná cílová pokladní kniha!');
            return;
        }

        $newCashbookId = CashbookId::fromString($values->newCashbookId);

        if(!$this->canEdit($this->cashbookId) || !$this->canEdit($newCashbookId)) {
            $this->presenter->flashMessage('Nemáte oprávnění k původní nebo nové pokladní knize!', 'danger');
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
     * Vrací pole ID => Název pro výpravy i tábory
     * @param string $eventType "general" or "camp"
     * @param array $states
     * @return array
     */
    private function getListOfEvents(string $eventType, array $states = NULL) : array
    {
        /** @var EventEntity $eventEntity */
        $eventEntity = $this->context->getService(($eventType === ObjectType::EVENT ? 'event' : $eventType) . 'Service');
        $eventService = $eventEntity->event;
        $rawArr = $eventService->getAll(date('Y'));

        if(empty($rawArr)) {
            return [];
        }

        $currentSkautisId = $this->getSkautisId($this->cashbookId);
        $resultArray = [];

        foreach ($rawArr as $item) {
            if($item['ID'] === $currentSkautisId) {
                continue;
            }

            if($states === NULL || in_array($item['ID_Event' . ucfirst($eventType) . 'State'], $states)) {
                $cashbookId = $this->getCashbookId($item['ID'], ObjectType::get($eventType));
                $resultArray[$cashbookId->toString()] = $item['DisplayName'];
            }
        }

        return $resultArray;
    }

    private function canEdit(CashbookId $cashbookId) : bool
    {
        $type = $this->getCashbookType($cashbookId);
        $skautisId = $this->getSkautisId($cashbookId);

        if($type->equalsValue(CashbookType::EVENT)) {
            return $this->authorizator->isAllowed(Event::UPDATE, $skautisId);
        }

        return $this->authorizator->isAllowed(Camp::UPDATE, $skautisId)
            || $this->authorizator->isAllowed(Camp::UPDATE_REAL, $skautisId)
            || $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $skautisId);
    }

    private function getCashbookType(CashbookId $cashbookId) : CashbookType
    {
        /** @var Cashbook $cashbook */
        $cashbook = $this->queryBus->handle(new CashbookQuery($cashbookId));
        return $cashbook->getType();
    }

    private function getCashbookId(int $skautisId, ObjectType $objectType) : CashbookId
    {
        if($objectType->equalsValue(ObjectType::CAMP)) {
            return $this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($skautisId)));
        }

        return $this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($skautisId)));
    }

    private function getSkautisId(CashbookId $cashbookId) : int
    {
        return $this->queryBus->handle(new SkautisIdQuery($cashbookId));
    }

}
