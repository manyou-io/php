<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Client;

use Manyou\BingHomepage\Record;
use Manyou\BingHomepage\RequestException;
use Manyou\BingHomepage\RequestParams;

interface ClientInterface
{
    /**
     * @return Record[]
     *
     * @throws RequestException
     */
    public function request(RequestParams ...$requests): iterable;
}
