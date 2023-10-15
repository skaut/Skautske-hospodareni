<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use AccountancyModule\Exception\IconvInvalid;
use Model\Cashbook\ReadModel\Queries\CashbookScansQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Common\File;
use Model\Common\IScanStorage;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Chit;

use function assert;
use function iconv;
use function sprintf;

final class CashbookScansQueryHandler
{
    public function __construct(private QueryBus $queryBus, private IScanStorage $storage)
    {
    }

    /** @return File[] */
    public function __invoke(CashbookScansQuery $query): array
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod($query->getPaymentMethod(), $query->getCashbookId()));

        $arr = [];
        foreach ($chits as $chit) {
            assert($chit instanceof Chit);
            foreach ($chit->getScans() as $scan) {
                $filename          = sprintf(
                    '%s_%s',
                    $chit->getName(),
                    $scan->getFilePath()->getOriginalFilename(),
                );
                $convertedFilename = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
                if (! $convertedFilename) {
                    throw new IconvInvalid(sprintf('Soubor %s nebylo možné přidat do exportu. Iconv selže', $filename));
                }

                $arr[$convertedFilename] = $this->storage->get($scan->getFilePath());
            }
        }

        return $arr;
    }
}
