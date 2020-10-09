<?php

declare(strict_types=1);

namespace Model\Infrastructure;

use Psr\Log\LoggerInterface;
use function preg_match;

// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
final class SQLLogger implements \Doctrine\DBAL\Logging\SQLLogger
{
    private LoggerInterface $logger;

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
        $context = [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ];

        if (preg_match('~^(INSERT|UPDATE|DELETE|START|BEGIN|COMMIT|ROLLBACK)~', $sql) === 1) {
            $this->logger->info('Mutating SQL query performed', $context);
        } else {
            $this->logger->debug('SQL query performed', $context);
        }
    }

    public function stopQuery() : void
    {
    }
}
