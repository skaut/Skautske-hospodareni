<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Cashbook\ReadModel\Queries\CashbookScansQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Common\File;
use Model\Common\IScanStorage;
use Model\DTO\Cashbook\Chit;
use function assert;
use function sprintf;

final class CashbookScansQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    /** @var IScanStorage */
    private $storage;

    public function __construct(QueryBus $queryBus, IScanStorage $storage)
    {
        $this->queryBus = $queryBus;
        $this->storage  = $storage;
    }

    /**
     * @return File[]
     */
    public function __invoke(CashbookScansQuery $query) : array
    {
        $chits = $this->queryBus->handle(ChitListQuery::withMethod($query->getPaymentMethod(), $query->getCashbookId()));

        $arr = [];
        foreach ($chits as $chit) {
            assert($chit instanceof Chit);
            foreach ($chit->getScans() as $scan) {
                $filename       = sprintf(
                    '%s_%s',
                    $chit->getName(),
                    $scan->getFilePath()->getOriginFilename()
                );
                $arr[$filename] = $this->storage->get($scan->getFilePath());
            }
        }

        return $arr;
    }
}
