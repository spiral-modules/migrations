<?php

/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Migrations\Migration;

use Spiral\Migrations\Exception\MigrationException;
use Spiral\Migrations\MigrationInterface;

/**
 * An interface for migrations providing information about the migration status.
 */
interface SynchronizedInterface
{
    /**
     * Alter associated migration state (new migration instance to be created).
     *
     * @param State $state
     * @return self
     */
    public function withState(State $state): self;

    /**
     * Get migration state.
     *
     * @return State
     *
     * @throws MigrationException When no state is presented.
     */
    public function getState(): State;
}
