<?php

declare(strict_types=1);

namespace Manyou\BingHomepage;

use DateTimeInterface;

use function rtrim;
use function sprintf;
use function str_pad;
use function strlen;

class Utils
{
    /** @see \Symfony\Component\VarDumper\Caster\DateCaster */
    public static function formatDateTime(DateTimeInterface $d): string
    {
        $tz     = $d->format('e');
        $offset = $d->format('P');
        $tz     = $tz === $offset ? " ${tz}" : " ${tz} (${offset})";

        return $d->format('Y-m-d H:i:' . self::formatSeconds($d->format('s'), $d->format('u'))) . $tz;
    }

    /** @see \Symfony\Component\VarDumper\Caster\DateCaster */
    public static function formatSeconds(string $s, string $us): string
    {
        return sprintf('%02d.%s', $s, 0 === ($len = strlen($t = rtrim($us, '0'))) ? '0' : ($len <= 3 ? str_pad($t, 3, '0') : $us));
    }
}
