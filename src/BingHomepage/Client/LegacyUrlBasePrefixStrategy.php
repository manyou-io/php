<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Client;

use Manyou\BingHomepage\RequestParams;

class LegacyUrlBasePrefixStrategy implements UrlBasePrefixStrategy
{
    public function getUrlBasePrefix(RequestParams $params): string
    {
        return '/az/hprichbg/rb/';
    }
}
