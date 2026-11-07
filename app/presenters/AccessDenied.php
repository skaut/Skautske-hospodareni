<?php

declare(strict_types=1);

namespace App;

use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

/** Access to a page or an action was denied; the message is written for the user and the 403 page shows it. */
final class AccessDenied extends BadRequestException
{
    public function __construct(string $message)
    {
        parent::__construct($message, IResponse::S403_Forbidden);
    }
}
