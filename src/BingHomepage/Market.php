<?php

declare(strict_types=1);

namespace Manyou\BingHomepage;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Stringable;

use function abs;

class Market implements Stringable
{
    public const ROW = 'ROW';
    public const US  = 'en-US';
    public const CA  = 'en-CA';
    public const QC  = 'fr-CA';
    public const UK  = 'en-GB';
    public const CN  = 'zh-CN';
    public const JP  = 'ja-JP';
    public const FR  = 'fr-FR';
    public const DE  = 'de-DE';
    public const IN  = 'en-IN';
    public const BR  = 'pt-BR';
    public const AU  = 'en-AU';
    public const IT  = 'it-IT';
    public const ES  = 'es-ES';

    public const MAPPINGS = [
        self::ROW => 'America/Los_Angeles', // UTC -8 / UTC -7
        self::US => 'America/Los_Angeles',
        self::AU => 'America/Los_Angeles',  // ROW; Australia/Sydney: UTC +10 / UTC +11
        self::BR => 'America/Sao_Paulo',    // UTC -3
        self::CA => 'America/Toronto',      // UTC -5 / UTC -4
        self::QC => 'America/Toronto',
        self::UK => 'Europe/London',        // UTC +0 / UTC +1
        self::FR => 'Europe/Paris',         // UTC +1 / UTC +2
        self::IT => 'Europe/Rome',
        self::ES => 'Europe/Madrid',
        self::DE => 'Europe/Berlin',
        self::IN => 'Asia/Kolkata',         // UTC +5:30
        self::CN => 'Asia/Shanghai',        // UTC +8
        self::JP => 'Asia/Tokyo',           // UTC +9
    ];

    /** @var string */
    private $name;

    /** @var DateTimeZone */
    private $timezone;

    public function __construct(string $name, ?DateTimeZone $tz = null)
    {
        if ($tz === null) {
            if (! isset(self::MAPPINGS[$name])) {
                throw new InvalidArgumentException("Market {$name} is unknown and no time zone provided");
            }

            $tz = new DateTimeZone(self::MAPPINGS[$name]);
        }

        $this->name     = $name;
        $this->timezone = $tz;
    }

    public function getToday(?DateTimeImmutable $today = null): DateTimeImmutable
    {
        if ($today === null) {
            return new DateTimeImmutable('today', $this->timezone);
        }

        return $today->setTimezone($this->timezone)->setTime(0, 0, 0);
    }

    public function getDate(DateTimeInterface $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date->format('Y-m-d'), $this->timezone);
    }

    /** Get the date "$offset" days before today */
    public function getDateBefore(int $offset, ?DateTimeImmutable $today = null): DateTimeImmutable
    {
        $today            = $this->getToday($today);
        $invert           = $offset < 0 ? 1 : 0;
        $offset           = (string) abs($offset);
        $interval         = new DateInterval("P{$offset}D");
        $interval->invert = $invert;

        return $today->sub($interval);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getTimeZone(): DateTimeZone
    {
        return $this->timezone;
    }
}
