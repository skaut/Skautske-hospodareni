<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Chit;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Commands\Cashbook\LockCashbook;
use App\Model\Cashbook\ObjectType;
use App\Model\Cashbook\ReadModel\Queries\CampCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\EventCashbookIdQuery;
use App\Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use App\Model\Common\UnitId;
use App\Model\DTO\Cashbook\UnitCashbook;
use App\Model\Event\Camp;
use App\Model\Event\Event;
use App\Model\Event\ReadModel\Queries\CampListQuery;
use App\Model\Event\ReadModel\Queries\EventListQuery;
use App\Presentation\Unit\Accessory\Components\ChitListControl;
use App\Presentation\Unit\Accessory\Factories\IChitListControlFactory;
use App\Presentation\Unit\UnitBasePresenter;
use LogicException;
use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Multiplier;
use Nette\Http\IResponse;

use function sprintf;

class ChitPresenter extends UnitBasePresenter
{
    /**
     * object type => [
     *      cashbook ID (without hyphens) => object
     * ].
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $cashbooks = [];

    #[Persistent]
    public int $onlyUnlocked = 1;

    public function __construct(private IChitListControlFactory $chitListFactory)
    {
        parent::__construct();
    }

    protected function startup(): void
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
        $this->redirect('default', ['unitId' => $officialUnitId, 'year' => $this->year, 'onlyUnlocked' => $this->onlyUnlocked]);
    }

    public function handleLockCashbook(string $cashbookId): void
    {
        $this->commandBus->handle(new LockCashbook(CashbookId::fromString($cashbookId), $this->getUser()->getId()));

        $this->flashMessage('Evidence plateb byla uzamčena', 'success');
        $this->redrawControl();
    }

    public function actionDefault(?int $year = null): void
    {
        $this->cashbooks = [
            ObjectType::UNIT => $this->getUnitCashbooks(),
            ObjectType::EVENT => $this->getEventCashbooks(),
            ObjectType::CAMP => $this->getCampCashbooks(),
        ];
    }

    public function renderDefault(): void
    {
        $this->template->setParameters([
            'types' => [
                ObjectType::EVENT => 'Výpravy',
                ObjectType::CAMP => 'Tábory',
                ObjectType::UNIT => 'Jednotky',
            ],
            'info' => $this->cashbooks,
            'isCashbookEmpty' => function (string $cashbookId): bool {
                $chitList = $this['chitList-'.$cashbookId];

                if (! $chitList instanceof ChitListControl) {
                    throw new LogicException('Assertion failed.');
                }

                return $chitList->isEmpty();
            },
        ]);
    }

    protected function createComponentChitList(): Multiplier
    {
        return new Multiplier(
            function (string $cashbookId): ChitListControl {
                $cashbookIdVo = CashbookId::fromString($cashbookId);
                if (! $this->canEditCashbook($cashbookIdVo)) {
                    throw new BadRequestException(sprintf('Cashbook #%s not found', $cashbookIdVo->toString()), IResponse::S404_NotFound);
                }

                return $this->chitListFactory->create($cashbookIdVo, (bool) $this->onlyUnlocked);
            },
        );
    }

    private function canEditCashbook(CashbookId $cashbookId): bool
    {
        foreach ($this->cashbooks as $cashbookList) {
            if (isset($cashbookList[$cashbookId->withoutHyphens()])) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, array<string, int|string>> */
    private function getUnitCashbooks(): array
    {
        $cashbooks = [];

        foreach ($this->unitService->getReadUnits($this->getUser()) as $id => $name) {
            foreach ($this->queryBus->handle(new UnitCashbookListQuery(new UnitId($id))) as $cashbook) {
                if (! $cashbook instanceof UnitCashbook) {
                    throw new LogicException('Assertion failed.');
                }
                $cashbooks[$cashbook->getCashbookId()->withoutHyphens()] = [
                    'ID' => $id,
                    'DisplayName' => $name.' '.$cashbook->getYear(),
                ];
            }
        }

        return $cashbooks;
    }

    /** @return array<string, array<string, int|string>> */
    private function getEventCashbooks(): array
    {
        $readableUnits = $this->unitService->getReadUnits($this->user);

        $cashbooks = [];

        foreach ($this->queryBus->handle(new EventListQuery($this->year)) as $event) {
            if (! $event instanceof Event) {
                throw new LogicException('Assertion failed.');
            }
            if (! isset($readableUnits[$event->getUnitId()->toInt()])) {
                continue;
            }

            $cashbookId = $this->queryBus->handle(new EventCashbookIdQuery($event->getId()));

            if (! $cashbookId instanceof CashbookId) {
                throw new LogicException('Assertion failed.');
            }
            $cashbooks[$cashbookId->withoutHyphens()] = [
                'ID' => $event->getId()->toInt(),
                'DisplayName' => $event->getDisplayName(),
            ];
        }

        return $cashbooks;
    }

    /** @return array<string, array<string, int|string>> */
    private function getCampCashbooks(): array
    {
        $readableUnits = $this->unitService->getReadUnits($this->user);

        $cashbooks = [];

        foreach ($this->queryBus->handle(new CampListQuery($this->year)) as $event) {
            if (! $event instanceof Camp) {
                throw new LogicException('Assertion failed.');
            }
            if (! isset($readableUnits[$event->getUnitId()->toInt()])) {
                continue;
            }

            $cashbookId = $this->queryBus->handle(new CampCashbookIdQuery($event->getId()));

            if (! $cashbookId instanceof CashbookId) {
                throw new LogicException('Assertion failed.');
            }
            $cashbooks[$cashbookId->withoutHyphens()] = [
                'ID' => $event->getId()->toInt(),
                'DisplayName' => $event->getDisplayName(),
            ];
        }

        return $cashbooks;
    }
}
