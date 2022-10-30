<?php

declare(strict_types=1);

namespace Manyou\BingHomepage;

use DateTimeImmutable;

class Image
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly DateTimeImmutable $debutOn,
        public readonly string $urlbase,
        public readonly string $copyright,
        public readonly bool $downloadable,
        public readonly ?array $video = null,
    ) {
    }

    public function with(mixed ...$args): self
    {
        return new self(...$args + (array) $this);
    }
}
