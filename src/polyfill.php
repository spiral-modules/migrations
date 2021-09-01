<?php

/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Migrations;

if (! \class_exists(\Spiral\Migrations\State::class)) {
    \class_alias(\Spiral\Migrations\Migration\State::class, \Spiral\Migrations\State::class);
}
