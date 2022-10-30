<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Parser;

use Manyou\BingHomepage\Record;

interface ParserInterface
{
    public function parse(array $data, string $market, string $urlBasePrefix): Record;
}
