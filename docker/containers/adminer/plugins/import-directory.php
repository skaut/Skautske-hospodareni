<?php

/**
 * Change server directory for imports.
 */
class AdminerImportDirectory
{
    /**
     * @return string
     */
    public function importServerPath()
    {
        return getenv('ADMINER_IMPORT_SERVER_PATH') ?: 'adminer.sql';
    }
}
