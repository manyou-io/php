<?php

declare(strict_types=1);

namespace Manyou\BingHomepage;

use DateTimeImmutable;

class Record
{
    public readonly string $imageId;

    public function __construct(
        public readonly string $id,
        public readonly Image $image,
        public readonly DateTimeImmutable $date,
        public readonly string $market,
        public readonly string $title,
        public readonly ?string $keyword = null,
        public readonly ?string $headline = null,
        public readonly ?string $description = null,
        public readonly ?string $quickfact = null,
        public readonly ?array $hotspots = null,
        public readonly ?array $messages = null,
        public readonly ?array $coverstory = null,
        ...$args,
    ) {
        $this->imageId = $image->id;
    }

    public function with(mixed ...$args): self
    {
        return new self(...$args + (array) $this);
    }

    public function getDateString(): string
    {
        return $this->date->format('Ymd');
    }
}
