<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Parser;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

use function parse_str;
use function parse_url;
use function preg_match;
use function urldecode;

use const PHP_URL_QUERY;

class Utils
{
    /** Parse "fullstartdate" string into DateTime with correct time zone of UTC offset type */
    public static function parseFullStartDate(string $fullStartDate, string $format): DateTimeImmutable
    {
        $d = DateTimeImmutable::createFromFormat($format, $fullStartDate, new DateTimeZone('UTC'));

        if ((int) $d->format('G') < 12) {
            // The moment of date change is the new date's 00:00
            // and UTC is on the new date.
            // Therefore, the timezone just reached the new date's 00:00
            // (just changed its date / just becomes the next day)
            // is slower than UTC.
            $tz = '-' . $d->format('H:i');
        } else {
            // But when UTC becomes 12:00, all UTC -* timezones
            // (the west side of the prime meridian)
            // already changed their date.
            // The fastest UTC +12 becomes the next new date
            // (tomorrow's date of UTC).
            $d24 = $d->modify('+1 day midnight');
            $tz  = $d->diff($d24, true)->format('%R%H:%I');
            $d   = $d24;
        }

        return new DateTimeImmutable($d->format('Y-m-d'), new DateTimeZone($tz));
    }

    /** Parse an URL of web search engine and extract keyword from its query string */
    public static function extractKeyword(string $url): ?string
    {
        $query = parse_url($url, PHP_URL_QUERY);

        if (! $query) {
            return null;
        }

        parse_str($query, $query);

        $fields = ['q', 'wd'];

        foreach ($fields as $field) {
            if (isset($query[$field]) && $query[$field] !== '') {
                return urldecode($query[$field]);
            }
        }

        return null;
    }

    /**
     * Normalize "urlbase" and extract image name from it
     *
     * @param string $urlBase e.g.
     *  "/az/hprichbg/rb/BemarahaNP_JA-JP15337355971" or
     *  "/th?id=OHR.BemarahaNP_JA-JP15337355971"
     *
     * @return string[] e.g.
     *  [
     *      "BemarahaNP_JA-JP15337355971",
     *      "BemarahaNP",
     *      "JA-JP15337355971"
     *  ]
     */
    public static function parseUrlBase(string $urlBase): array
    {
        $regex   = '/(\w+)_((?:ROW|[A-Z]{2}-[A-Z]{2})\d+)/';
        $matches = [];

        if (preg_match($regex, $urlBase, $matches) !== 1) {
            throw new InvalidArgumentException("Failed to parse URL base {$urlBase}");
        }

        return $matches;
    }
}
