<?php

declare(strict_types=1);

namespace Model\Infrastructure;

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;
use function preg_match;

// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
final class MutatingSQLLogger implements SQLLogger
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string       $sql
     * @param mixed[]|null $params
     * @param mixed[]|null $types
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null) : void
    {
        if (preg_match('~^(INSERT|UPDATE|DELETE)~', $sql) !== 1) {
            return;
        }

        $this->logger->debug('SQL query performed', [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ]);
    }

    public function stopQuery() : void
    {
    }
}
