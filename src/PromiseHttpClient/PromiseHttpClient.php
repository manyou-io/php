<?php

declare(strict_types=1);

namespace Manyou\PromiseHttpClient;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use SplObjectStorage;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_callable;
use function is_int;
use function usleep;

/**
 * Make a Symfony HttpClient to return Guzzle promises.
 *
 * @see \Symfony\Component\HttpClient\HttplugClient
 */
final class PromiseHttpClient implements PromiseHttpClientInterface
{
    private SplObjectStorage $promisePool;
    private WaitLoop $waitLoop;

    public function __construct(private HttpClientInterface $client)
    {
        $this->promisePool = new SplObjectStorage();
        $this->waitLoop    = new WaitLoop($this->client, $this->promisePool);
    }

    public function request(string $method, string $url, array $options = []): PromiseInterface
    {
        $delayMs = $options['delay'] ?? null;
        unset($options['delay']);

        try {
            $response = $this->client->request($method, $url, $options);
        } catch (TransportExceptionInterface $e) {
            return new RejectedPromise($e);
        }

        $promisePool = $this->promisePool;
        $waitLoop    = $this->waitLoop;

        $promise = new Promise(
            static function () use ($response, $waitLoop) {
                $waitLoop->wait($response);
            },
            static function () use ($response, $promisePool) {
                $response->cancel();
                $promisePool->detach($response);
            },
        );

        $promisePool->attach($response, $promise);

        if (is_int($delayMs) && $delayMs > 0) {
            if (! is_callable($pause = $response->getInfo('pause_handler'))) {
                return (new FulfilledPromise(null))->then(static function () use ($delayMs, $promise) {
                    usleep($delayMs * 1000);

                    return $promise;
                });
            }

            $pause($delayMs / 1e3);
        }

        return $promise;
    }

    /**
     * Resolves pending promises that complete before the timeouts are reached.
     *
     * When $maxDuration is null and $idleTimeout is reached, promises are rejected.
     *
     * @return int The number of remaining pending promises
     */
    public function wait(?float $maxDuration = null, ?float $idleTimeout = null): int
    {
        return $this->waitLoop->wait(null, $maxDuration, $idleTimeout);
    }

    public function __destruct()
    {
        $this->wait();
    }
}
