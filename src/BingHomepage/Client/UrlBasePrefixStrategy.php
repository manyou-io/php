<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Client;

use Manyou\BingHomepage\RequestParams;

interface UrlBasePrefixStrategy
{
    public function getUrlBasePrefix(RequestParams $params): string;
}
