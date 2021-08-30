<?php

/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Migrations;

use Spiral\Migrations\Exception\MigrationException;
use Spiral\Migrations\Migration\SynchronizedInterface;

interface MigrationInterface extends SynchronizedInterface
{
    /**
     * Target migration database. Each migration must be specific
     * to one database only.
     *
     * @return null|string
     */
    public function getDatabase(): ?string;

    /**
     * Lock migration into specific migration capsule.
     *
     * @param CapsuleInterface $capsule
     * @return self
     */
    public function withCapsule(CapsuleInterface $capsule): MigrationInterface;

    /**
     * Up migration.
     *
     * @throws MigrationException
     */
    public function up();

    /**
     * Rollback migration.
     *
     * @throws MigrationException
     */
    public function down();
}
