<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Parser;

use InvalidArgumentException;
use Manyou\BingHomepage\Image;
use Manyou\BingHomepage\ObjectId;
use Manyou\BingHomepage\Record;

use function preg_match;
use function preg_replace;

class MediaContentParser implements ParserInterface
{
    /**
     * Extract the author and/or the stock photo agency from "copyright" string
     */
    public static function parseCopyright(string $copyright): string
    {
        $trim      = '/^[\pZ\pC]+|[\pZ\pC]+$/u';
        $copyright = preg_replace($trim, '', $copyright);

        $regex   = '/^\x{00A9}\pZ*(.+?)$/u';
        $matches = [];

        if (preg_match($regex, $copyright, $matches) !== 1) {
            throw new InvalidArgumentException("Failed to parse copyright string ${copyright}");
        }

        return $matches[1];
    }

    public function parse(array $data, string $market, string $urlBasePrefix): Record
    {
        $date = Utils::parseFullStartDate($data['Ssd'], 'Ymd_Hi');

        [$urlbase, $imageName] = Utils::parseUrlBase($data['ImageContent']['Image']['Url']);

        $image = new Image(
            id: ObjectId::create(),
            name: $imageName,
            debutOn: $date,
            urlbase: $urlBasePrefix . $urlbase,
            copyright: self::parseCopyright($data['ImageContent']['Copyright']),
            downloadable: $data['ImageContent']['Image']['Downloadable'],
            video: $data['VideoContent'],
        );

        $quickfact = $data['ImageContent']['QuickFact']['MainText'];

        return new Record(
            id: ObjectId::create(),
            image: $image,
            date: $date,
            market: $market,
            title: $data['ImageContent']['Title'],
            keyword: Utils::extractKeyword($data['ImageContent']['BackstageUrl']),
            headline: $data['ImageContent']['Headline'],
            description: $data['ImageContent']['Description'],
            quickfact: empty($quickfact) ? null : $quickfact,
        );
    }
}
