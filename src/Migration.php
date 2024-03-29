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
use Spiral\Database\DatabaseInterface;
use Spiral\Migrations\Exception\MigrationException;
use Spiral\Migrations\Migration\DefinitionInterface;
use Spiral\Migrations\Migration\ProvidesSyncStateInterface;
use Spiral\Migrations\Migration\State;

/**
 * Simple migration class with shortcut for database and blueprint instances.
 */
abstract class Migration implements MigrationInterface
{
    // Target migration database
    protected const DATABASE = null;

    /** @var State|null */
    private $state;

    /** @var CapsuleInterface */
    private $capsule;

    /**
     * {@inheritDoc}
     */
    public function getDatabase(): ?string
    {
        return static::DATABASE;
    }

    /**
     * {@inheritdoc}
     */
    public function withCapsule(CapsuleInterface $capsule): DefinitionInterface
    {
        $migration = clone $this;
        $migration->capsule = $capsule;

        return $migration;
    }

    /**
     * {@inheritdoc}
     */
    public function withState(State $state): ProvidesSyncStateInterface
    {
        $migration = clone $this;
        $migration->state = $state;

        return $migration;
    }

    /**
     * {@inheritdoc}
     */
    public function getState(): State
    {
        if (empty($this->state)) {
            throw new MigrationException('Unable to get migration state, no state are set');
        }

        return $this->state;
    }

    /**
     * @return Database
     */
    protected function database(): DatabaseInterface
    {
        if (empty($this->capsule)) {
            throw new MigrationException('Unable to get database, no capsule are set');
        }

        return $this->capsule->getDatabase();
    }

    /**
     * Get table schema builder (blueprint).
     *
     * @param string $table
     * @return TableBlueprint
     */
    protected function table(string $table): TableBlueprint
    {
        if (empty($this->capsule)) {
            throw new MigrationException('Unable to get table blueprint, no capsule are set');
        }

        return new TableBlueprint($this->capsule, $table);
    }
}
