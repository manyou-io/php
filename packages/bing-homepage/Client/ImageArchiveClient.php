<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Client;

use GuzzleHttp\Promise\PromiseInterface;
use Manyou\BingHomepage\Parser\ImageArchiveParser;
use Manyou\BingHomepage\Parser\ParserInterface;
use Manyou\BingHomepage\RequestException;
use Manyou\BingHomepage\RequestParams;
use Manyou\PromiseHttpClient\PromiseHttpClientInterface;
use Psr\Log\LoggerAwareInterface;

final class ImageArchiveClient implements ClientInterface, LoggerAwareInterface
{
    use ClientTrait;

    private ImageArchiveParser $parser;

    public function __construct(
        UrlBasePrefixStrategy $prefixStrategy,
        private PromiseHttpClientInterface $httpClient,
        private string $endpoint = 'https://global.bing.com/HPImageArchive.aspx',
    ) {
        $this->prefixStrategy = $prefixStrategy;
        $this->parser         = new ImageArchiveParser();
    }

    private function getCacheKey(RequestParams $params): string
    {
        return $params->getMarket() . $this->getIndex($params);
    }

    private function makeRequest(RequestParams $params): PromiseInterface
    {
        return $this->httpClient->request('GET', $this->endpoint, [
            'query' => [
                'format' => 'js',
                'idx' => $this->getIndex($params),
                'n' => '8',
                'video' => '1',
                'mkt' => $params->getMarket(),
            ],
            'max_redirects' => 0,
        ]);
    }

    private function getResultsKey(): string
    {
        return 'images';
    }

    private function getResultOffset(RequestParams $params): int
    {
        $offset = $params->getOffset();

        return $offset > 7 ? $offset - 7 : $offset;
    }

    private function validateParams(RequestParams $params): void
    {
        $offset = $params->getOffset();

        if ($offset < 0 || $offset > 14) {
            throw new RequestException('Offset out of range: 0 to 14', $params);
        }
    }

    private function getParser(): ParserInterface
    {
        return $this->parser;
    }

    private function getIndex(RequestParams $params): string
    {
        return $params->getOffset() > 7 ? '7' : '0';
    }
}
