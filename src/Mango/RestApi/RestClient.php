<?php

declare(strict_types=1);

namespace Manyou\Mango\RestApi;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RestClient implements Client
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private NormalizerInterface $normalizer,
    ) {
    }

    public function request(Request $request): ResponseInterface
    {
        $options = ['query' => $this->normalizer->normalize($request, null, ['groups' => ['query']])];

        $options += match ($request->getMethod()) {
            'POST', 'PUT', 'PATCH' => ['json' => $this->normalizer->normalize($request, null, ['groups' => ['rest']])],
            default => [],
        };

        return $this->httpClient->request($request->getMethod(), $request->getPath(), $options);
    }
}