<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Client;

use Manyou\BingHomepage\RequestParams;

class CalendarUrlBasePrefixStrategy implements UrlBasePrefixStrategy
{
    public function __construct(private string $prefix = '/a/', private string $dateFormat = 'Y/n/j/')
    {
    }

    public function getUrlBasePrefix(RequestParams $params): string
    {
        return $this->prefix . $params->getDate()->format($this->dateFormat);
    }
}
