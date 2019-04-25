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
use Model\DTO\Cashbook\UnitCashbook;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\EventEntity;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Multiplier;
use Nette\Http\IResponse;
use RuntimeException;
use function array_filter;
use function array_map;
use function assert;
use function sprintf;

class ChitPresenter extends BasePresenter
{
    /** @var mixed[] */
    public $info;

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
        $officialUnitId = $this->unitService->getOfficialUnit($this->aid)->getId();

        if ($officialUnitId === $this->aid) {
            return;
        }

        $this->flashMessage('Přehled paragonů je dostupný jen pro organizační jednotky.');
        $this->redirect('this', ['aid' => $officialUnitId]);
    }

    public function handleLockCashbook(string $cashbookId) : void
    {
        $this->commandBus->handle(new LockCashbook(CashbookId::fromString($cashbookId), $this->getUser()->getId()));

        $this->flashMessage('Evidence plateb byla uzamčena', 'success');
        $this->redrawControl();
    }

    public function actionDefault(?int $year = null) : void
    {
        $this->info = [];
        $units      = [];

        foreach ($this->unitService->getReadUnits($this->getUser()) as $ik => $iu) {
            $units[$ik]['DisplayName'] = $iu;
        }

        $eventService = $this->context->getService('eventService');
        $campService  = $this->context->getService('campService');

        assert($eventService instanceof EventEntity && $campService instanceof EventEntity);

        $objectsByType = [
            ObjectType::UNIT => $units,
            ObjectType::EVENT => $eventService->getEvent()->getAll($this->year),
            ObjectType::CAMP => $campService->getEvent()->getAll($this->year),
        ];

        foreach ($objectsByType as $type => $objects) {
            $this->cashbooks[$type] = $this->getAllChitsByObjectId(ObjectType::get($type), $objects);
        }
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
     * @param mixed[] $objects
     *
     * @return mixed[]
     */
    private function getAllChitsByObjectId(ObjectType $objectType, array $objects) : array
    {
        if ($objectType->equalsValue(ObjectType::UNIT)) {
            $cashbooks = [];

            foreach ($objects as $unitId => ['DisplayName' => $name]) {
                $unitCashbooks = $this->queryBus->handle(new UnitCashbookListQuery($unitId));

                foreach ($unitCashbooks as $cashbook) {
                    assert($cashbook instanceof UnitCashbook);

                    $cashbooks[$cashbook->getCashbookId()->withoutHyphens()] = [
                        'ID' => $unitId,
                        'DisplayName' => $name . ' ' . $cashbook->getYear(),
                    ];
                }
            }

            return $cashbooks;
        }

        $readableUnits = $this->unitService->getReadUnits($this->user);

        $objects = array_filter(
            $objects,
            function (array $object) use ($readableUnits) : bool {
                return isset($readableUnits[$object['ID_Unit']]);
            }
        );

        $cashbooks = [];
        foreach ($objects as $oid => $object) {
            foreach ($this->getCashbookIds($objectType, $oid) as $cashbookId) {
                $cashbooks[$cashbookId->withoutHyphens()] = $object;
            }
        }

        return $cashbooks;
    }

    /**
     * @return CashbookId[]
     */
    private function getCashbookIds(ObjectType $object, int $objectId) : array
    {
        if ($object->equalsValue(ObjectType::CAMP)) {
            return [$this->queryBus->handle(new CampCashbookIdQuery(new SkautisCampId($objectId)))];
        }

        if ($object->equalsValue(ObjectType::EVENT)) {
            return [$this->queryBus->handle(new EventCashbookIdQuery(new SkautisEventId($objectId)))];
        }

        if ($object->equalsValue(ObjectType::UNIT)) {
            $unitCashbooks = $this->queryBus->handle(new UnitCashbookListQuery($objectId));

            return array_map(
                function (UnitCashbook $cashbook) : CashbookId {
                    return $cashbook->getCashbookId();
                },
                $unitCashbooks
            );
        }

        throw new RuntimeException('Unknown cashbook type');
    }
}
