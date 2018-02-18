<?php

declare(strict_types=1);

namespace App\AccountancyModule\Components\Cashbook;

use App\AccountancyModule\Components\BaseControl;
use App\Forms\BaseForm;
use eGen\MessageBus\Bus\QueryBus;
use Model\Auth\IAuthorizator;
use Model\Auth\Resources\Camp;
use Model\Auth\Resources\Event;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CashbookTypeQuery;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
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

    /** @var int */
    private $cashbookId;

    /** @var QueryBus */
    private $queryBus;

    /** @var IAuthorizator */
    private $authorizator;

    /** @var Container */
    private $context;

    public function __construct(int $cashbookId, QueryBus $queryBus, IAuthorizator $authorizator, Container $context)
    {
        parent::__construct();
        $this->cashbookId = $cashbookId;
        $this->queryBus = $queryBus;
        $this->authorizator = $authorizator;
        $this->context = $context;
    }

    /**
     * @param int[] $chitIds
     */
    public function open(array $chitIds): void
    {
        $this->chitIds = implode(',', $chitIds);
        $this->opened = TRUE;
        $this->redrawControl();
    }

    public function render(): void
    {
        $this->template->setParameters([
            'renderModal' => $this->opened,
        ]);

        $this->template->setFile(__DIR__ . '/templates/MoveChitsDialog.latte');
        $this->template->render();
    }

    protected function createComponentForm(): BaseForm
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


        $form->onSuccess[] = function (BaseForm $form, ArrayHash $values): void {
            $this->formSubmitted($form, $values);
        };

        return $form;
    }

    private function formSubmitted(BaseForm $form, ArrayHash $values): void
    {
        $chitIds = $this->getChitIds();

        if (empty($chitIds)) {
            $form->addError('Nebyly vybrány žádné paragony!');
            return;
        }

        $newCashbookId = $values->newCashbookId;

        if ($newCashbookId === NULL) {
            $form->addError('Nebyla vybrána žádná cílová pokladní kniha!');
            return;
        }

        $currentCashbookType = $this->getCashbookType($this->cashbookId);
        $newCashbookType = $this->getCashbookType($newCashbookId);

        if ( ! $this->canEdit($this->cashbookId) || ! $this->canEdit($newCashbookId)) {
            $this->presenter->flashMessage('Nemáte oprávnění k původní nebo nové pokladní knize!', 'danger');
            $this->redirect('this');
        }

        $this->getEventEntity()->chits->moveChits(
            $chitIds,
            $this->getSkautisId($this->cashbookId),
            $currentCashbookType->getSkautisObjectType()->getValue(),
            $this->getSkautisId($newCashbookId),
            $newCashbookType->getSkautisObjectType()->getValue()
        );


        $this->redirect('this');
    }

    /**
     * @return int[]
     */
    private function getChitIds(): array
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
    private function getListOfEvents(string $eventType, array $states = NULL): array
    {
        /** @var EventEntity $eventEntity */
        $eventEntity = $this->context->getService(($eventType === ObjectType::EVENT ? 'event' : $eventType) . 'Service');
        $eventService = $eventEntity->event;
        $rawArr = $eventService->getAll(date('Y'));

        if (empty($rawArr)) {
            return [];
        }

        $currentSkautisId = $this->getSkautisId($this->cashbookId);
        $resultArray = [];

        foreach ($rawArr as $item) {
            if ($item['ID'] === $currentSkautisId) {
                continue;
            }

            if ($states === NULL || in_array($item['ID_Event' . ucfirst($eventType) . 'State'], $states)) {
                $resultArray[$eventEntity->chits->getCashbookIdFromSkautisId($item['ID'])] = $item['DisplayName'];
            }
        }

        return $resultArray;
    }

    private function canEdit(int $cashbookId): bool
    {
        $type = $this->getCashbookType($cashbookId);
        $skautisId = $this->getSkautisId($cashbookId);

        if ($type->equalsValue(CashbookType::EVENT)) {
            return $this->authorizator->isAllowed(Event::UPDATE, $skautisId);
        }

        return $this->authorizator->isAllowed(Camp::UPDATE, $skautisId)
            || $this->authorizator->isAllowed(Camp::UPDATE_REAL, $skautisId)
            || $this->authorizator->isAllowed(Camp::UPDATE_REAL_COST, $skautisId);
    }

    private function getCashbookType(int $cashbookId): CashbookType
    {
        return $this->queryBus->handle(new CashbookTypeQuery($cashbookId));
    }

    private function getEventEntity(): EventEntity
    {
        $type = $this->getCashbookType($this->cashbookId)->getSkautisObjectType()->getValue();

        if ($type === ObjectType::UNIT) {
            $serviceName = 'unitAccountService';
        } else {
            $serviceName = ($type === ObjectType::EVENT ? 'event' : $type) . 'Service';
        }

        return $this->context->getService($serviceName);
    }

    private function getSkautisId(int $cashbookId): int
    {
        return $this->queryBus->handle(new SkautisIdQuery($cashbookId));
    }

}
