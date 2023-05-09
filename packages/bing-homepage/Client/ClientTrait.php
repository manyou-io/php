<?php

declare(strict_types=1);

namespace Manyou\BingHomepage\Client;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Manyou\BingHomepage\Parser\ParserInterface;
use Manyou\BingHomepage\RequestException;
use Manyou\BingHomepage\RequestParams;
use Manyou\PromiseHttpClient\PromiseHttpClientInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

use function sprintf;

trait ClientTrait
{
    use LoggerAwareTrait;

    private PromiseHttpClientInterface $httpClient;

    /** @var PromiseInterface[] */
    private array $cache;

    private UrlBasePrefixStrategy $prefixStrategy;

    abstract private function getCacheKey(RequestParams $params): string;

    abstract private function makeRequest(RequestParams $params): PromiseInterface;

    abstract private function getResultsKey(): string;

    abstract private function getResultOffset(RequestParams $params): int;

    abstract private function validateParams(RequestParams $params): void;

    abstract private function getParser(): ParserInterface;

    private function getResponse(RequestParams $params): PromiseInterface
    {
        $this->validateParams($params);

        $cacheKey = $this->getCacheKey($params);

        $response = $this->cache[$cacheKey] ??= $this->makeRequest($params)->then(function (ResponseInterface $response) use ($params) {
            $resultsKey = $this->getResultsKey();

            try {
                $data = $response->toArray();
            } catch (ExceptionInterface $e) {
                throw new RequestException($e->getMessage(), $params, null, $e);
            }

            if (empty($data[$resultsKey])) {
                throw new RequestException(sprintf('Got empty results on key "%s"', $resultsKey), $params);
            }

            return $data[$resultsKey];
        });

        return $response->then(function (array $result) use ($params) {
            $offset = $this->getResultOffset($params);

            if (empty($result[$offset])) {
                throw new RequestException(sprintf('Got empty result on offset %d', $offset), $params);
            }

            return $result[$offset];
        });
    }

    private function requestOne(RequestParams $params): PromiseInterface
    {
        return $this->getResponse($params)->then(function (array $result) use ($params) {
            try {
                $record = $this->getParser()->parse($result, $params->getMarket(), $this->prefixStrategy->getUrlBasePrefix($params));
            } catch (Throwable $e) {
                throw new RequestException($e->getMessage(), $params, null, $e);
            }

            if (! $params->isDateExpected($record->date)) {
                throw new RequestException('Got unexpected date', $params, $record->date);
            }

            if (! $params->isTimeZoneOffsetExpected($record->date)) {
                $e = new RequestException('The actual time zone offset differs from expected', $params, $record->date);
                $this->logger && $this->logger->warning($e->getMessage());
            }

            return $record;
        });
    }

    public function request(RequestParams ...$requests): array
    {
        // initialize or reset cache
        $this->cache = [];

        foreach ($requests as $i => $params) {
            $requests[$i] = $this->requestOne($params);
        }

        return Utils::unwrap($requests);
    }
}
