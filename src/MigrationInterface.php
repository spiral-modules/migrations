<?php

/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Migrations;

use Spiral\Migrations\Migration\DefinitionInterface;
use Spiral\Migrations\Migration\ProvidesSyncStateInterface;

interface MigrationInterface extends ProvidesSyncStateInterface, DefinitionInterface
{

}
