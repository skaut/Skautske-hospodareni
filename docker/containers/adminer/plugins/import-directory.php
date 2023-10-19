<?php

declare(strict_types=1);

/**
 * Change server directory for imports.
 */
class AdminerImportDirectory
{
    public function importServerPath(): string
    {
        return getenv('ADMINER_IMPORT_SERVER_PATH') ?: 'adminer.sql';
    }
}
