<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers\Pdf;

use App\Model\Cashbook\Cashbook\CashbookType;
use App\Model\Cashbook\Cashbook\PaymentMethod;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\CampParticipantListQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookOfficialUnitQuery;
use App\Model\Cashbook\ReadModel\Queries\CashbookQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitListQuery;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantIncomeQuery;
use App\Model\Cashbook\ReadModel\Queries\EventParticipantListQuery;
use App\Model\Cashbook\ReadModel\Queries\Pdf\ExportChits;
use App\Model\Cashbook\ReadModel\Queries\SkautisIdQuery;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Cashbook;
use App\Model\DTO\Cashbook\Chit;
use App\Model\DTO\Cashbook\ChitItem;
use App\Model\Event\Functions;
use App\Model\Event\ReadModel\Queries\CampFunctions;
use App\Model\Event\ReadModel\Queries\EventFunctions;
use App\Model\Event\SkautisCampId;
use App\Model\Event\SkautisEventId;
use App\Model\Services\TemplateFactory;
use App\Model\Unit\Unit;
use Doctrine\Common\Collections\ArrayCollection;
use LogicException;

use function array_filter;
use function count;
use function in_array;

class ExportChitsHandler
{
    public function __construct(
        private QueryBus $queryBus,
        private TemplateFactory $templateFactory,
    ) {
    }

    public function __invoke(ExportChits $query): string
    {
        $chits = new ArrayCollection($this->queryBus->handle(ChitListQuery::withMethod(PaymentMethod::CASH(), $query->getCashbookId())));

        if ($query->getChitIds() !== null) {
            $ids = $query->getChitIds();
            $chits = $chits->filter(function (Chit $chit) use ($ids): bool {
                return in_array($chit->getId(), $ids, true);
            });
        }

        [$income, $outcome] = $chits->partition(function (int|string $_x, Chit $chit): bool {
            return $chit->isIncome();
        });

        $activeHpd = $chits->exists(function (int|string $_x, Chit $chit): bool {
            return 0 < count(array_filter($chit->getItems(), function (ChitItem $item) {
                return $item->getCategory()->getShortcut() === 'hpd';
            }));
        });

        $cashbook = $this->queryBus->handle(new CashbookQuery($query->getCashbookId()));

        if (! $cashbook instanceof Cashbook) {
            throw new LogicException('Assertion failed.');
        }
        $cashbookType = $cashbook->getType();

        $template = [];

        $skautisId = $this->queryBus->handle(new SkautisIdQuery($query->getCashbookId()));

        $skautisId = $cashbookType->equalsValue(CashbookType::CAMP)
            ? new SkautisCampId($skautisId)
            : new SkautisEventId($skautisId);

        $officialUnit = $this->queryBus->handle(new CashbookOfficialUnitQuery($query->getCashbookId()));
        if (! $officialUnit instanceof Unit) {
            throw new LogicException('Assertion failed.');
        }
        $template['officialName'] = $officialUnit->getFullDisplayNameWithAddress(true);
        $template['cashbook'] = $cashbook;

        // HPD
        if ($activeHpd) {
            $template['totalPayment'] = $this->queryBus->handle($skautisId instanceof SkautisCampId
                    ? CampParticipantIncomeQuery::all($skautisId)
                    : new EventParticipantIncomeQuery($skautisId));

            $functionsQuery = $skautisId instanceof SkautisCampId
                ? new CampFunctions($skautisId)
                : new EventFunctions($skautisId);

            $functions = $this->queryBus->handle($functionsQuery);

            if (! $functions instanceof Functions) {
                throw new LogicException('Assertion failed.');
            }
            $accountant = $functions->getAccountant() ?? $functions->getLeader();
            $template['pokladnik'] = $accountant?->getName() ?? '';

            $template['list'] = $this->queryBus->handle(
                $skautisId instanceof SkautisCampId
                    ? new CampParticipantListQuery($skautisId)
                    : new EventParticipantListQuery($skautisId),
            );
        }

        $template['income'] = $income;
        $template['outcome'] = $outcome;

        return $this->templateFactory->create(__DIR__.'/templates/chits.latte', $template);
    }
}
