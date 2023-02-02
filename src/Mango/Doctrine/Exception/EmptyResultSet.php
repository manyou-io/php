<?php

declare(strict_types=1);

namespace Manyou\Mango\Doctrine\Exception;

use RuntimeException;

class EmptyResultSet extends RuntimeException
{
    public static function create()
    {
        return new self('Empty result set.');
    }
}
