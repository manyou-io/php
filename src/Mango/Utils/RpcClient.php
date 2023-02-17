<?php

declare(strict_types=1);

namespace Manyou\Mango\Utils;

use Prisma\Contracts\ApiRequest;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RpcClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private NormalizerInterface $normalizer,
    ) {
    }

    public function request(ApiRequest $request): ResponseInterface
    {
        return $this->httpClient->request(
            $request->getMethod(),
            $request->getPath(),
            ['json' => $this->normalizer->normalize($request, null, ['groups' => ['rest']])],
        );
    }
}
