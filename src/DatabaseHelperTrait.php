<?php
declare(strict_types=1);

namespace Tyler36\CypressCake;

use Cake\Datasource\ConnectionManager;

/**
 * Class DatabaseHelperTrait.
 */
trait DatabaseHelperTrait
{
    /**
     * Truncate all tables.
     *
     * @return void
     */
    private static function truncateAllTables(): void
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('default');
        $tables = $connection->getSchemaCollection()->listTables();

        $isUsingSqlite = str_contains($connection->config()['driver'], 'Sqlite');
        if ($isUsingSqlite) {
            // SQLite does not have an explicit TRUNCATE TABLE command like other databases.
            foreach ($tables as $table) {
                $connection->execute("DELETE FROM $table");
            }

            return;
        }

        if (!self::isPostgres($connection)) {
            // Disable foreign key checks
            $connection->execute('SET FOREIGN_KEY_CHECKS = 0;');
        }

        foreach ($tables as $table) {
            $connection->execute("TRUNCATE TABLE $table");
        }


        if (!self::isPostgres($connection)) {
            // Re-enable foreign key checks
            $connection->execute('SET FOREIGN_KEY_CHECKS = 1;');
        }

        if (self::isPostgres($connection)) {
            // Reset auto-generated sequences
            $connection->execute("ALTER SEQUENCE {$table}_id_seq RESTART WITH 1;");
        }
    }

    /**
     * Check if connection is Postgres
     *
     * @return bool
     */
    private static function isPostgres($connection): bool
    {
        return str_contains($connection->config()['driver'], 'Postgres');
    }
}
