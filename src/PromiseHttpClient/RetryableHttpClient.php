<?php

declare(strict_types=1);

namespace Manyou\PromiseHttpClient;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Manyou\PromiseHttpClient\DelayStrategy\DelayStrategyChain;
use Manyou\PromiseHttpClient\RetryStrategy\DefaultRetryStrategy;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class RetryableHttpClient implements PromiseHttpClientInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private RetryStrategyInterface $retryStrategy;

    private DelayStrategyInterface $delayStrategy;

    public function __construct(
        private PromiseHttpClientInterface $client,
        ?RetryStrategyInterface $retryStrategy = null,
        ?DelayStrategyInterface $delayStrategy = null,
        private int $maxRetries = 3,
        ?LoggerInterface $logger = null,
    ) {
        $this->retryStrategy = $retryStrategy ?? new DefaultRetryStrategy();
        $this->delayStrategy = $delayStrategy ?? DelayStrategyChain::createDefault();

        if (null !== $logger) {
            $this->setLogger($logger);
        }
    }

    public function request(string $method, string $url, array $options = []): PromiseInterface
    {
        return $this->doRequest($method, $url, $options, 0);
    }

    private function doRequest(string $method, string $url, array $options, int $count): PromiseInterface
    {
        $promise = $this->client->request($method, $url, $options);

        if ($count < $this->maxRetries) {
            return $promise->then(
                $this->onFulfilled($method, $url, $options, $count),
                $this->onRejected($method, $url, $options, $count),
            );
        }

        $this->logger && $this->logger->debug(
            'Reached max retries of {maxRetries}',
            ['method' => $method, 'url' => $url, 'count' => $count, 'maxRetries' => $this->maxRetries],
        );

        return $promise;
    }

    private function onFulfilled(string $method, string $url, array $options, int $count): callable
    {
        return function (ResponseInterface $response) use ($method, $url, $options, $count) {
            $context = ['method' => $method, 'url' => $url, 'statusCode' => $response->getStatusCode(), 'count' => $count];

            if ($this->retryStrategy->onResponse($response)) {
                $this->logger && $this->logger->info('Retrying on response of status {statusCode}', $context);

                return $this->doRetry($method, $url, $options, $count, $response);
            }

            $this->logger && $this->logger->debug('Returning response of status {statusCode}', $context);

            return $response;
        };
    }

    private function onRejected(string $method, string $url, array $options, int $count): callable
    {
        return function ($reason) use ($method, $url, $options, $count) {
            if ($reason instanceof TransportExceptionInterface) {
                $context = ['method' => $method, 'url' => $url, 'exception' => $reason, 'count' => $count];

                if ($this->retryStrategy->onException($reason)) {
                    $this->logger && $this->logger->info('Retrying on exception', $context);

                    return $this->doRetry($method, $url, $options, $count, null);
                }

                $this->logger && $this->logger->debug('Rejecting with exception', $context);
            }

            return new RejectedPromise($reason);
        };
    }

    private function doRetry(string $method, string $url, array $options, int $count, ?ResponseInterface $response): PromiseInterface
    {
        $delay   = $this->delayStrategy->getDelay(++$count, $response);
        $context = ['method' => $method, 'url' => $url, 'count' => $count, 'delay' => $delay];

        if ($delay !== null && $delay > 0) {
            $this->logger && $this->logger->info('Retrying #{count} with {delay} ms delay', $context);

            return $this->doRequest($method, $url, ['delay' => $delay] + $options, $count);
        }

        $this->logger && $this->logger->info('Retrying #{count} without delay', $context);

        return $this->doRequest($method, $url, $options, $count);
    }
}
