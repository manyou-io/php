<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Parser;

use DateTimeImmutable;
use DateTimeZone;
use Manyou\BingHomepage\Image;
use Manyou\BingHomepage\ObjectId;
use Manyou\BingHomepage\Record;
use RuntimeException;

use function array_shift;
use function preg_match;
use function preg_replace;

class ImageArchiveParser implements ParserInterface
{
    /**
     * Extract image description as well as the author and/or
     * the stock photo agency from "copyright" string
     *
     * @return string[] [$description, $copyright]
     */
    public static function parseCopyright(string $copyright): array
    {
        $trim      = '/^[\pZ\pC]+|[\pZ\pC]+$/u';
        $copyright = preg_replace($trim, '', $copyright);

        $regex   = '/^(.+?)\pZ*(?:\(|\x{FF08})\pZ*\x{00A9}\pZ*(.+?)\pZ*(?:\)|\x{FF09})$/u';
        $matches = [];

        if (preg_match($regex, $copyright, $matches) !== 1) {
            throw new RuntimeException("Failed to parse copyright string ${copyright}");
        }

        array_shift($matches);

        return $matches;
    }

    public function parse(array $data, string $market, string $urlBasePrefix): Record
    {
        $date    = Utils::parseFullStartDate($data['fullstartdate'], 'YmdHi');
        $debutOn = DateTimeImmutable::createFromFormat('!Ymd', $date->format('Ymd'), new DateTimeZone('UTC'));

        [$urlbase, $imageName] = Utils::parseUrlBase($data['urlbase']);

        [$title, $copyright] = self::parseCopyright($data['copyright']);

        $image = new Image(
            id: ObjectId::create(),
            name: $imageName,
            debutOn: $debutOn,
            urlbase: $urlBasePrefix . $urlbase,
            copyright: $copyright,
            downloadable: $data['wp'],
            video: empty($data['vid']) ? null : $data['vid'],
        );

        return new Record(
            id: ObjectId::create(),
            image: $image,
            date: $date,
            market: $market,
            title: $title,
            keyword: Utils::extractKeyword($data['copyrightlink']),
            headline: empty($data['title']) ? null : $data['title'],
            hotspots: empty($data['hs']) ? null : $data['hs'],
            messages: empty($data['msg']) ? null : $data['msg'],
        );
    }
}
