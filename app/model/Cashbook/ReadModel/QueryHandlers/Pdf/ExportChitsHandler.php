<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers\Pdf;

use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook\CashbookType;
use Model\Cashbook\Cashbook\PaymentMethod;
use Model\Cashbook\ReadModel\Queries\CashbookOfficialUnitQuery;
use Model\Cashbook\ReadModel\Queries\CashbookQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Cashbook\ReadModel\Queries\Pdf\ExportChits;
use Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use Model\Common\ShouldNotHappen;
use Model\DTO\Cashbook\Cashbook;
use Model\DTO\Cashbook\Chit;
use Model\DTO\Cashbook\ChitItem;
use Model\Event\Functions;
use Model\Event\ReadModel\Queries\CampFunctions;
use Model\Event\ReadModel\Queries\EventFunctions;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\IEventServiceFactory;
use Model\IParticipantServiceFactory;
use Model\Services\TemplateFactory;
use Model\Unit\Unit;
use Model\UnitService;
use function array_filter;
use function assert;
use function count;
use function in_array;
use function sprintf;
use function ucfirst;

class ExportChitsHandler
{
    /** @var IParticipantServiceFactory */
    private $participantServiceFactory;

    /** @var QueryBus */
    private $queryBus;

    /** @var IEventServiceFactory */
    private $serviceFactory;

    /** @var UnitService */
    private $unitService;

    /** @var TemplateFactory */
    private $templateFactory;

    public function __construct(
        IParticipantServiceFactory $participantServiceFactory,
        IEventServiceFactory $serviceFactory,
        QueryBus $queryBus,
        UnitService $unitService,
        TemplateFactory $templateFactory
    ) {
        $this->participantServiceFactory = $participantServiceFactory;
        $this->queryBus                  = $queryBus;
        $this->serviceFactory            = $serviceFactory;
        $this->unitService               = $unitService;
        $this->templateFactory           = $templateFactory;
    }

    public function __invoke(ExportChits $query) : string
    {
        $chits = new ArrayCollection($this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $query->getCashbookId())));

        if ($query->getChitIds() !== null) {
            $ids   = $query->getChitIds();
            $chits = $chits->filter(function (Chit $chit) use ($ids) : bool {
                return in_array($chit->getId(), $ids, true);
            });
        }

        [$income, $outcome] = $chits->partition(function ($_, Chit $chit) : bool {
            return $chit->isIncome();
        });

        $activeHpd = $chits->exists(function ($_, Chit $chit) : bool {
            return 0 < count(array_filter($chit->getItems(), function (ChitItem $item) {
                    return $item->getCategory()->getShortcut() === 'hpd';
            }));
        });

        $cashbook = $this->queryBus->handle(new CashbookQuery($query->getCashbookId()));

        assert($cashbook instanceof Cashbook);

        $cashbookType = $cashbook->getType();

        $template = [];

        $skautisId = $this->queryBus->handle(new SkautisIdQuery($query->getCashbookId()));

        if ($cashbookType->isUnit()) {
            $officialUnit = $this->unitService->getOfficialUnit($skautisId);
        } elseif (in_array($cashbookType->getValue(), [CashbookType::EVENT, CashbookType::CAMP])) {
            $officialUnit = $this->queryBus->handle(new CashbookOfficialUnitQuery($query->getCashbookId()));
        } else {
            throw new ShouldNotHappen(sprintf('Invalid cashbook type: %s', $cashbookType->getValue()));
        }
        assert($officialUnit instanceof Unit);
        $template['officialName'] = $officialUnit->getFullDisplayNameWithAddress();
        $template['cashbook']     = $cashbook;

        //HPD
        if ($activeHpd) {
            $participantService       = $this->participantServiceFactory->create(ucfirst($cashbook->getType()->getValue()));
            $template['totalPayment'] = $participantService->getTotalPayment($skautisId);

            $functionsQuery = $cashbookType->equalsValue(CashbookType::CAMP)
                ? new CampFunctions(new SkautisCampId($skautisId))
                : new EventFunctions(new SkautisEventId($skautisId));

            $functions = $this->queryBus->handle($functionsQuery);

            assert($functions instanceof Functions);

            $accountant            = $functions->getAccountant() ?? $functions->getLeader();
            $template['pokladnik'] = $accountant !== null ? $accountant->getName() : '';

            $template['list'] = $participantService->getAll($skautisId);
        }

        $template['income']  = $income;
        $template['outcome'] = $outcome;

        return $this->templateFactory->create(__DIR__ . '/templates/chits.latte', $template);
    }
}
