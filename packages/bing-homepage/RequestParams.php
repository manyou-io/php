<?php

declare(strict_types=1);

namespace Manyou\BingHomepage;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class RequestParams
{
    private Market $market;

    private DateTimeImmutable $date;

    private int $offset;

    private function __construct()
    {
    }

    public static function createToday(Market $market, ?DateTimeImmutable $today = null): self
    {
        $instance         = new self();
        $instance->market = $market;
        $instance->date   = $market->getToday($today);
        $instance->offset = 0;

        return $instance;
    }

    public static function create(Market $market, DateTimeInterface $date, ?DateTimeImmutable $today = null): self
    {
        $instance         = new self();
        $instance->market = $market;
        $instance->date   = $market->getDate($date);
        $instance->offset = $instance->getDaysAgo($today);

        return $instance;
    }

    public static function createFromOffset(Market $market, int $offset, ?DateTimeImmutable $today = null): self
    {
        $instance         = new self();
        $instance->market = $market;
        $instance->date   = $market->getDateBefore($offset, $today);
        $instance->offset = $offset;

        return $instance;
    }

    /** Get how many days ago was "$date" */
    public function getDaysAgo(?DateTimeImmutable $today = null): int
    {
        $today = $this->market->getToday($today);
        $diff  = $this->date->diff($today, false);

        return (int) $diff->format('%r%a');
    }

    public function isDateExpected(DateTimeInterface $date): bool
    {
        return $date->format('Y-m-d') === $this->date->format('Y-m-d');
    }

    public function isTimeZoneOffsetExpected(DateTimeInterface $date): bool
    {
        return $date->format('Z') === $this->date->format('Z');
    }

    public function getMarketTimeZone(): Market
    {
        return $this->market;
    }

    public function getMarket(): string
    {
        return $this->market->getName();
    }

    public function getTimeZone(): DateTimeZone
    {
        return $this->market->getTimeZone();
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }
}
