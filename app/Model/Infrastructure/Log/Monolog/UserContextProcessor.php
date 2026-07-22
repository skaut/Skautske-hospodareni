<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Log\Monolog;

use App\Model\Infrastructure\Log\UserContextProvider;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class UserContextProcessor implements ProcessorInterface
{
    public function __construct(private UserContextProvider $userContext)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $userData = $this->userContext->getUserData();

        if ($userData === null) {
            return $record;
        }

        return $record->with(extra: [...$record->extra, 'user' => $userData]);
    }
}
