<?php

/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Migrations;

use Spiral\Database\Database;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Table;
use Spiral\Migrations\Config\MigrationConfig;
use Spiral\Migrations\Exception\MigrationException;
use Spiral\Migrations\Migrator\MigrationsTable;

final class Migrator implements MigratorInterface
{
    private const DB_DATE_FORMAT = 'Y-m-d H:i:s';

    private const MIGRATION_TABLE_FIELDS_LIST = [
        'id',
        'migration',
        'time_executed',
        'created_at'
    ];

    /** @var MigrationConfig */
    private $config;

    /** @var DatabaseManager */
    private $dbal;

    /** @var RepositoryInterface */
    private $repository;

    /**
     * @param MigrationConfig     $config
     * @param DatabaseManager     $dbal
     * @param RepositoryInterface $repository
     */
    public function __construct(
        MigrationConfig $config,
        DatabaseManager $dbal,
        RepositoryInterface $repository
    ) {
        $this->config = $config;
        $this->repository = $repository;
        $this->dbal = $dbal;
    }

    /**
     * @return MigrationConfig
     */
    public function getConfig(): MigrationConfig
    {
        return $this->config;
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * {@inheritDoc}
     */
    public function isConfigured(): bool
    {
        foreach ($this->dbal->getDatabases() as $db) {
            if (!$this->checkMigrationTableStructure($db)) {
                return false;
            }
        }

        return !$this->isRestoreMigrationDataRequired();
    }

    /**
     * {@inheritDoc}
     */
    public function configure(): void
    {
        if ($this->isConfigured()) {
            return;
        }

        $this->createMigrationTables($this->dbal->getDatabases());

        if ($this->isRestoreMigrationDataRequired()) {
            $this->restoreMigrationData();
        }
    }

    /**
     * Create migration table inside given databases list
     *
     * @param iterable<Database> $databases
     */
    private function createMigrationTables(iterable $databases): void
    {
        foreach ($databases as $database) {
            $this->createMigrationTable($database);
        }
    }

    /**
     * Create migration table inside given database
     *
     * @param Database $database
     */
    private function createMigrationTable(Database $database): void
    {
        $table = new MigrationsTable($database, $this->config->getTable());
        $table->actualize();
    }

    /**
     * Get every available migration with valid meta information.
     *
     * @return MigrationInterface[]
     */
    public function getMigrations(): array
    {
        $result = [];

        foreach ($this->repository->getMigrations() as $migration) {
            // Populating migration state and execution time (if any)
            $result[] = $migration->withState($this->resolveState($migration));
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function run(CapsuleInterface $capsule = null): ?MigrationInterface
    {
        if (!$this->isConfigured()) {
            throw new MigrationException('Unable to run migration, Migrator not configured');
        }

        foreach ($this->getMigrations() as $migration) {
            if ($migration->getState()->getStatus() !== State::STATUS_PENDING) {
                continue;
            }

            try {
                $capsule = $capsule ?? new Capsule($this->dbal->database($migration->getDatabase()));
                $capsule->getDatabase()->transaction(
                    static function () use ($migration, $capsule): void {
                        $migration->withCapsule($capsule)->up();
                    }
                );

                $this->migrationTable($migration->getDatabase())->insertOne(
                    [
                        'migration' => $migration->getState()->getName(),
                        'time_executed' => new \DateTime('now'),
                        'created_at' => $this->getMigrationCreatedAtForDb($migration),
                    ]
                );

                return $migration->withState($this->resolveState($migration));
            } catch (\Throwable $exception) {
                throw new MigrationException(
                    \sprintf(
                        'Error in the migration (%s) occurred: %s',
                        \sprintf(
                            '%s (%s)',
                            $migration->getState()->getName(),
                            $migration->getState()->getTimeCreated()->format(self::DB_DATE_FORMAT)
                        ),
                        $exception->getMessage()
                    ),
                    $exception->getCode(),
                    $exception
                );
            }
        }

        return null;
    }

    /**
     * @param CapsuleInterface|null $capsule
     * @return MigrationInterface|null
     * @throws \Throwable
     */
    public function rollback(CapsuleInterface $capsule = null): ?MigrationInterface
    {
        if (!$this->isConfigured()) {
            throw new MigrationException('Unable to run migration, Migrator not configured');
        }

        /** @var MigrationInterface $migration */
        foreach (array_reverse($this->getMigrations()) as $migration) {
            if ($migration->getState()->getStatus() !== State::STATUS_EXECUTED) {
                continue;
            }

            $capsule = $capsule ?? new Capsule($this->dbal->database($migration->getDatabase()));
            $capsule->getDatabase()->transaction(
                static function () use ($migration, $capsule): void {
                    $migration->withCapsule($capsule)->down();
                }
            );

            $migrationData = $this->fetchMigrationData($migration);

            if (!empty($migrationData)) {
                $this->migrationTable($migration->getDatabase())
                    ->delete(['id' => $migrationData['id']])
                    ->run();
            }

            return $migration->withState($this->resolveState($migration));
        }

        return null;
    }

    /**
     * Clarify migration state with valid status and execution time
     *
     * @param MigrationInterface $migration
     * @return State
     * @throws \Exception
     */
    private function resolveState(MigrationInterface $migration): State
    {
        $db = $this->dbal->database($migration->getDatabase());

        $data = $this->fetchMigrationData($migration);

        if (empty($data['time_executed'])) {
            return $migration->getState()->withStatus(State::STATUS_PENDING);
        }

        return $migration->getState()->withStatus(
            State::STATUS_EXECUTED,
            new \DateTimeImmutable($data['time_executed'], $db->getDriver()->getTimezone())
        );
    }

    /**
     * Migration table, all migration information will be stored in it.
     *
     * @param string|null $database
     * @return Table
     */
    private function migrationTable(string $database = null): Table
    {
        return $this->dbal->database($database)->table($this->config->getTable());
    }

    /**
     * @param Database $db
     * @return bool
     */
    private function checkMigrationTableStructure(Database $db): bool
    {
        $table = new MigrationsTable($db, $this->config->getTable());

        return $table->isPresent();
    }

    /**
     * Fetch migration information from database
     *
     * @param MigrationInterface $migration
     *
     * @return array|null
     */
    private function fetchMigrationData(MigrationInterface $migration): ?array
    {
        $migrationData = $this->migrationTable($migration->getDatabase())
            ->select('id', 'time_executed', 'created_at')
            ->where(
                [
                    'migration' => $migration->getState()->getName(),
                    'created_at' => $this->getMigrationCreatedAtForDb($migration)->format(self::DB_DATE_FORMAT),
                ]
            )
            ->run()
            ->fetch();

        return is_array($migrationData) ? $migrationData : [];
    }

    private function restoreMigrationData(): void
    {
        foreach ($this->repository->getMigrations() as $migration) {
            $migrationData = $this->migrationTable($migration->getDatabase())
                ->select('id')
                ->where(
                    [
                        'migration' => $migration->getState()->getName(),
                        'created_at' => null,
                    ]
                )
                ->run()
                ->fetch();

            if (!empty($migrationData)) {
                $this->migrationTable($migration->getDatabase())
                    ->update(
                        ['created_at' => $this->getMigrationCreatedAtForDb($migration)],
                        ['id' => $migrationData['id']]
                    )
                    ->run();
            }
        }
    }

    /**
     * Check if some data modification required
     *
     * @param iterable<Database>|null $databases
     * @return bool
     */
    private function isRestoreMigrationDataRequired(iterable $databases = null): bool
    {
        $databases = $databases ?? $this->dbal->getDatabases();

        foreach ($databases as $db) {
            $table = $db->table($this->config->getTable());

            if (
                $table->select('id')
                    ->where(['created_at' => null])
                    ->count() > 0
            ) {
                return true;
            }
        }

        return false;
    }

    private function getMigrationCreatedAtForDb(MigrationInterface $migration): \DateTimeInterface
    {
        $db = $this->dbal->database($migration->getDatabase());

        return \DateTimeImmutable::createFromFormat(
            self::DB_DATE_FORMAT,
            $migration->getState()->getTimeCreated()->format(self::DB_DATE_FORMAT),
            $db->getDriver()->getTimezone()
        );
    }
}
