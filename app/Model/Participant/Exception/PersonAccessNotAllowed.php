<?php

declare(strict_types=1);

namespace App\Model\Participant;

use Exception;
use Throwable;

use function sprintf;

/**
 * SkautIS refuses to show or change personal data of a person the current user has no permission for,
 * e.g. a camp participant whose person record belongs to another unit.
 */
class PersonAccessNotAllowed extends Exception
{
    public function __construct(int $personId, Throwable $previous)
    {
        parent::__construct(sprintf('SkautIS does not allow access to personal data of person %d.', $personId), 0, $previous);
    }
}
