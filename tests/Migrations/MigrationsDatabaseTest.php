<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Migrations\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Driver\SQLite\SQLiteDriver;
use Spiral\Files\Files;
use Spiral\Migrations\Config\MigrationConfig;
use Spiral\Migrations\FileRepository;
use Spiral\Migrations\Migrator;

class MigrationsDatabaseTest extends TestCase
{
    public const DRIVER = 'sqlite';

    public function testSkipReadonly(): void
    {
        $this->generateMigration('20200909.024119_333_333_migration_1.php', 'A3');

        // Standard behavior
        $this->assertFalse($this->migrator(false)->isConfigured());

        // Ignore created migrations in case the connection is readonly
        $this->assertTrue($this->migrator(true)->isConfigured());
    }

    private function generateMigration(string $file, string $class): string
    {
        $out = __DIR__ . '/../files/' . $file;

        file_put_contents($out, sprintf(file_get_contents(__DIR__ . '/../files/migration.stub'), $class));

        return $out;
    }

    /**
     * @param bool $readonly
     * @return Migrator
     */
    private function migrator(bool $readonly): Migrator
    {
        $config = new MigrationConfig([
            'directory' => __DIR__ . '/../files/',
            'table'     => 'migrations',
            'safe'      => true,
        ]);

        return new Migrator(
            $config,
            $this->dbal($readonly),
            new FileRepository($config, new Container())
        );
    }

    /**
     * @param bool $readonly
     * @return DatabaseManager
     */
    private function dbal(bool $readonly): DatabaseManager
    {
        return new DatabaseManager(
            new DatabaseConfig([
                'default'   => 'default',
                'databases' => [
                    'default' => ['driver' => 'test'],
                ],
                'drivers'   => [
                    'test' => [
                        'driver'  => SQLiteDriver::class,
                        'options' => [
                            'connection' => 'sqlite::memory:',
                            'readonly'   => $readonly,
                            'username'   => 'sqlite',
                            'password'   => '',
                        ],
                    ],
                ],
            ])
        );
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        $files = new Files();
        foreach ($files->getFiles(__DIR__ . '/../files/', '*.php') as $file) {
            $files->delete($file);
            clearstatcache(true, $file);
        }

        parent::tearDown();
    }
}
