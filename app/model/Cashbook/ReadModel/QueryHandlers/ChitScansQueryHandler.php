<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\Cashbook;
use Model\Cashbook\ReadModel\Queries\ChitQuery;
use Model\Cashbook\ReadModel\Queries\ChitScansQuery;
use Model\Common\File;
use Model\Common\IScanStorage;
use Model\DTO\Cashbook\Chit;
use function array_map;
use function assert;

final class ChitScansQueryHandler
{
    private QueryBus $queryBus;

    private IScanStorage $storage;

    public function __construct(QueryBus $queryBus, IScanStorage $storage)
    {
        $this->queryBus = $queryBus;
        $this->storage  = $storage;
    }

    /**
     * @return File[]
     */
    public function __invoke(ChitScansQuery $query) : array
    {
        $chit = $this->queryBus->handle(new ChitQuery($query->getCashbookId(), $query->getChitId()));
        assert($chit instanceof Chit);

        return array_map(
            function (Cashbook\ChitScan $scan) : File {
                return $this->storage->get($scan->getFilePath());
            },
            $chit->getScans()
        );
    }
}
