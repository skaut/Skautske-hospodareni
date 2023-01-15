<?php

declare(strict_types=1);

namespace Model\Infrastructure\Log\Monolog;

use Model\Infrastructure\Log\UserContextProvider;
use Monolog\Processor\ProcessorInterface;

class UserContextProcessor implements ProcessorInterface
{
    public function __construct(private UserContextProvider $userContext)
    {
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public function __invoke(array $record): array
    {
        $userData = $this->userContext->getUserData();

        if ($this->userContext->getUserData() !== null) {
            $record['extra']['user'] = $userData;
        }

        return $record;
    }
}
