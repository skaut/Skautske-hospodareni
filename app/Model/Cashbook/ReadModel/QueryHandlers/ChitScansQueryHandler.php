<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\Cashbook;
use App\Model\Cashbook\ReadModel\Queries\ChitQuery;
use App\Model\Cashbook\ReadModel\Queries\ChitScansQuery;
use App\Model\Common\File;
use App\Model\Common\IScanStorage;
use App\Model\Common\Services\QueryBus;
use App\Model\DTO\Cashbook\Chit;

use function array_map;
use function assert;

final class ChitScansQueryHandler
{
    public function __construct(private QueryBus $queryBus, private IScanStorage $storage)
    {
    }

    /** @return File[] */
    public function __invoke(ChitScansQuery $query): array
    {
        $chit = $this->queryBus->handle(new ChitQuery($query->getCashbookId(), $query->getChitId()));
        assert($chit instanceof Chit);

        return array_map(
            function (Cashbook\ChitScan $scan): File {
                return $this->storage->get($scan->getFilePath());
            },
            $chit->getScans(),
        );
    }
}
