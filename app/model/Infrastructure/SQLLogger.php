<?php

declare(strict_types=1);

namespace Model\Infrastructure;

use Psr\Log\LoggerInterface;

use function preg_match;

// phpcs:disable SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
final class SQLLogger implements \Doctrine\DBAL\Logging\SQLLogger
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param string       $sql
     * @param mixed[]|null $params
     * @param mixed[]|null $types
     */
    // phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    public function startQuery($sql, array|null $params = null, array|null $types = null): void
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

    public function stopQuery(): void
    {
    }
}
