<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Parser;

use DateTimeImmutable;
use DateTimeZone;
use Manyou\BingHomepage\Image;
use Manyou\BingHomepage\ObjectId;
use Manyou\BingHomepage\Record;
use RuntimeException;

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

        $regex   = '/\x{00A9}\pZ*(.+?)$/u';
        $matches = [];

        if (preg_match($regex, $copyright, $matches) !== 1) {
            throw new RuntimeException("Failed to parse copyright string {$copyright}");
        }

        return $matches[1];
    }

    public function parse(array $data, string $market, string $urlBasePrefix): Record
    {
        $date    = Utils::parseFullStartDate($data['Ssd'], 'Ymd_Hi');
        $debutOn = DateTimeImmutable::createFromFormat('!Ymd', $date->format('Ymd'), new DateTimeZone('UTC'));

        [$urlbase, $imageName] = Utils::parseUrlBase($data['ImageContent']['Image']['Url']);

        $title = $data['ImageContent']['Title'];

        try {
            $copyright = self::parseCopyright($data['ImageContent']['Copyright']);
        } catch (RuntimeException) {
        }

        if (! isset($copyright)) {
            [$title, $copyright] = ImageArchiveParser::parseCopyright($title);
        }

        $image = new Image(
            id: ObjectId::create(),
            name: $imageName,
            debutOn: $debutOn,
            urlbase: $urlBasePrefix . $urlbase,
            copyright: $copyright,
            downloadable: $data['ImageContent']['Image']['Downloadable'],
            video: $data['VideoContent'],
        );

        $quickfact = $data['ImageContent']['QuickFact']['MainText'];

        return new Record(
            id: ObjectId::create(),
            image: $image,
            date: $date,
            market: $market,
            title: $title,
            keyword: Utils::extractKeyword($data['ImageContent']['BackstageUrl']),
            headline: $data['ImageContent']['Headline'],
            description: $data['ImageContent']['Description'],
            quickfact: empty($quickfact) ? null : $quickfact,
        );
    }
}
