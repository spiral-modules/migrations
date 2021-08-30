<?php

/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Migrations\Migration;

interface StateInterface
{
    /**
     * @return string
     */
    public function getName(): string;
}
