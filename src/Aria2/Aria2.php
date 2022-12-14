<?php

declare(strict_types=1);

namespace Manyou\Aria2;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function array_unshift;

class Aria2
{
    private HttpClientInterface $client;

    private ?string $auth = null;

    public function __construct(private string $url, ?string $secret = null, ?HttpClientInterface $client = null)
    {
        $this->client = ($client ??= HttpClient::create());

        if ($secret !== null) {
            $this->auth = "token:$secret";
        }
    }

    public function request(string $method, array $params)
    {
        if (isset($this->auth)) {
            array_unshift($params, $this->auth);
        }

        $request = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => "aria2.$method",
            'params' => $params,
        ];

        try {
            $response = $this->client->request('POST', $this->url, ['json' => $request])->toArray(false);
        } catch (DecodingExceptionInterface $jsonException) {
            throw new Aria2Exception('Failed to decode JSON message', 0, $jsonException);
        }

        if (isset($response['error'])) {
            throw new Aria2Exception($response['error']['message'], $response['error']['code']);
        }

        return $response['result'];
    }

    public function __call(string $name, array $arguments)
    {
        $this->request($name, $arguments);
    }
}
