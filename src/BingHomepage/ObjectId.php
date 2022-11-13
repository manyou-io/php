<?php

declare(strict_types=1);

namespace Manyou\BingHomepage;

use function bin2hex;
use function random_bytes;
use function random_int;
use function sprintf;
use function time;

class ObjectId
{
    private static string $processId;

    private static int $counter;

    public static function create(): string
    {
        $counterResetted = false;
        if (! isset(self::$counter) || ($counterResetted = self::$counter > 16777215)) {
            self::$counter = random_int(0, 16777215);
        }

        if (! isset(self::$processId) || $counterResetted) {
            self::$processId = bin2hex(random_bytes(5));
        }

        return sprintf('%08x%s%06x', time(), self::$processId, self::$counter++);
    }
}
