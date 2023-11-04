<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\CashbookScansQuery;
use Model\Cashbook\ReadModel\Queries\ChitListQuery;
use Model\Common\File;
use Model\Common\IScanStorage;
use Model\Common\Services\QueryBus;
use Model\DTO\Cashbook\Chit;
use Nette\Utils\Strings;

use function assert;
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
                $filename = sprintf(
                    '%s_%s',
                    self::__sanitize($chit->getName()),
                    self::__sanitize($scan->getFilePath()->getOriginalFilename()),
                );

                $arr[$filename] = $this->storage->get($scan->getFilePath());
            }
        }

        return $arr;
    }

    private function __sanitize(string $s): string
    {
        return Strings::toAscii(Strings::fixEncoding($s));
    }
}
