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

        // Disable foreign key checks
        $connection->execute('SET FOREIGN_KEY_CHECKS = 0;');

        foreach ($tables as $table) {
            $connection->execute("TRUNCATE TABLE $table");
        }

        // Re-enable foreign key checks
        $connection->execute('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
