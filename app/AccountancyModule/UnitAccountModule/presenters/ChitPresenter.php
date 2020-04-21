<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule;

use App\AccountancyModule\UnitAccountModule\Components\ChitListControl;
use App\AccountancyModule\UnitAccountModule\Factories\IChitListControlFactory;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Commands\Cashbook\LockCashbook;
use Model\Cashbook\ObjectType;
use Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use Model\Common\UnitId;
use Model\DTO\Cashbook\UnitCashbook;
use Model\Event\Camp;
use Model\Event\Event;
use Model\Event\ReadModel\Queries\CampListQuery;
use Model\Event\ReadModel\Queries\EventListQuery;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Multiplier;
use Nette\Http\IResponse;
use function assert;
use function sprintf;

class ChitPresenter extends BasePresenter
{
    /**
     * object type => [
     *      cashbook ID (without hyphens) => object
     * ]
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    private $cashbooks = [];

    /**
     * @persistent
     * @var int
     */
    public $onlyUnlocked = 1;

    /** @var IChitListControlFactory */
    private $chitListFactory;

    public function __construct(IChitListControlFactory $chitListFactory)
    {
        parent::__construct();
        $this->chitListFactory = $chitListFactory;
    }

    protected function startup() : void
    {
        parent::startup();
        $this->template->setParameters([
            'onlyUnlocked' => $this->onlyUnlocked,
        ]);
        $officialUnitId = $this->unitService->getOfficialUnit($this->unitId->toInt())->getId();

        if ($officialUnitId === $this->unitId->toInt()) {
            return;
        }

        $this->flashMessage('Přehled paragonů je dostupný jen pro organizační jednotky.');
        $this->redirect('this', ['unitId' => $officialUnitId]);
    }

    public function handleLockCashbook(string $cashbookId) : void
    {
        $this->commandBus->handle(new LockCashbook(CashbookId::fromString($cashbookId), $this->getUser()->getId()));

        $this->flashMessage('Evidence plateb byla uzamčena', 'success');
        $this->redrawControl();
    }

    public function actionDefault(?int $year = null) : void
    {
        $this->cashbooks = [
            ObjectType::UNIT => $this->getUnitCashbooks(),
            ObjectType::EVENT => $this->getEventCashbooks(),
            ObjectType::CAMP => $this->getCampCashbooks(),
        ];
    }

    public function renderDefault() : void
    {
        $this->template->setParameters([
            'types' => [
                ObjectType::EVENT => 'Výpravy',
                ObjectType::CAMP => 'Tábory',
                ObjectType::UNIT => 'Jednotky',
            ],
            'info'            => $this->cashbooks,
            'isCashbookEmpty' => function (string $cashbookId) : bool {
                $chitList = $this['chitList-' . $cashbookId];

                assert($chitList instanceof ChitListControl);

                return $chitList->isEmpty();
            },
        ]);
    }

    protected function createComponentChitList() : Multiplier
    {
        return new Multiplier(
            function (string $cashbookId) : ChitListControl {
                $cashbookIdVo = CashbookId::fromString($cashbookId);
                if (! $this->canEditCashbook($cashbookIdVo)) {
                    throw new BadRequestException(sprintf('Cashbook #%s not found', $cashbookIdVo->toString()), IResponse::S404_NOT_FOUND);
                }

                return $this->chitListFactory->create($cashbookIdVo, (bool) $this->onlyUnlocked);
            }
        );
    }

    private function canEditCashbook(CashbookId $cashbookId) : bool
    {
        foreach ($this->cashbooks as $cashbookList) {
            if (isset($cashbookList[$cashbookId->withoutHyphens()])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array<string, int|string>>
     */
    private function getUnitCashbooks() : array
    {
        $cashbooks = [];

        foreach ($this->unitService->getReadUnits($this->getUser()) as $id => $name) {
            foreach ($this->queryBus->handle(new UnitCashbookListQuery(new UnitId($id))) as $cashbook) {
                assert($cashbook instanceof UnitCashbook);

                $cashbooks[$cashbook->getCashbookId()->withoutHyphens()] = [
                    'ID' => $id,
                    'DisplayName' => $name . ' ' . $cashbook->getYear(),
                ];
            }
        }

        return $cashbooks;
    }

    /**
     * @return array<string, array<string, int|string>>
     */
    private function getEventCashbooks() : array
    {
        $readableUnits = $this->unitService->getReadUnits($this->user);

        $cashbooks = [];

        foreach ($this->queryBus->handle(new EventListQuery($this->year)) as $event) {
            assert($event instanceof Event);

            if (! isset($readableUnits[$event->getUnitId()->toInt()])) {
                continue;
            }

            $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery($event->getId()));

            assert($cashbookId instanceof CashbookId);

            $cashbooks[$cashbookId->withoutHyphens()] = [
                'ID' => $event->getId()->toInt(),
                'DisplayName' => $event->getDisplayName(),
            ];
        }

        return $cashbooks;
    }

    /**
     * @return array<string, array<string, int|string>>
     */
    private function getCampCashbooks() : array
    {
        $readableUnits = $this->unitService->getReadUnits($this->user);

        $cashbooks = [];

        foreach ($this->queryBus->handle(new CampListQuery($this->year)) as $event) {
            assert($event instanceof Camp);

            if (! isset($readableUnits[$event->getUnitId()->toInt()])) {
                continue;
            }

            $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($event->getId()));

            assert($cashbookId instanceof CashbookId);

            $cashbooks[$cashbookId->withoutHyphens()] = [
                'ID' => $event->getId()->toInt(),
                'DisplayName' => $event->getDisplayName(),
            ];
        }

        return $cashbooks;
    }
}
