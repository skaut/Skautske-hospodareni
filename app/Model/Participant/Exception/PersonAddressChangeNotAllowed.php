<?php

declare(strict_types=1);

namespace App\Model\Participant;

use Exception;
use Throwable;

use function sprintf;

/**
 * SkautIS allows address changes through PersonUpdateBasic only for simple persons (created just as participants),
 * not for persons with a full record, e.g. former members.
 */
class PersonAddressChangeNotAllowed extends Exception
{
    public function __construct(int $personId, Throwable $previous)
    {
        parent::__construct(sprintf('SkautIS does not allow changing the address of person %d.', $personId), 0, $previous);
    }
}
