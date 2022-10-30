<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Client;

use GuzzleHttp\Promise\PromiseInterface;
use Manyou\BingHomepage\Parser\MediaContentParser;
use Manyou\BingHomepage\Parser\ParserInterface;
use Manyou\BingHomepage\RequestException;
use Manyou\BingHomepage\RequestParams;
use Manyou\PromiseHttpClient\PromiseHttpClientInterface;
use Psr\Log\LoggerAwareInterface;

final class MediaContentClient implements ClientInterface, LoggerAwareInterface
{
    use ClientTrait;

    private MediaContentParser $parser;

    public function __construct(
        UrlBasePrefixStrategy $prefixStrategy,
        private PromiseHttpClientInterface $httpClient,
        private string $endpoint = 'https://www.bing.com/hp/api/model',
    ) {
        $this->prefixStrategy = $prefixStrategy;
        $this->parser         = new MediaContentParser();
    }

    private function getCacheKey(RequestParams $params): string
    {
        return $params->getMarket();
    }

    private function makeRequest(RequestParams $params): PromiseInterface
    {
        return $this->httpClient->request('GET', $this->endpoint, [
            'query' => ['mkt' => $params->getMarket()],
            'max_redirects' => 0,
        ]);
    }

    private function getResultsKey(): string
    {
        return 'MediaContents';
    }

    private function getResultOffset(RequestParams $params): int
    {
        return $params->getOffset();
    }

    private function validateParams(RequestParams $params): void
    {
        $offset = $params->getOffset();

        if ($offset < 0 || $offset > 6) {
            throw new RequestException('Offset out of range: 0 to 6', $params);
        }
    }

    private function getParser(): ParserInterface
    {
        return $this->parser;
    }
}
