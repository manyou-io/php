<?php

declare(strict_types=1);

namespace Manyou\Mango\Utils;

use ApiPlatform\Metadata\Post;
use InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function substr;

class RpcClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private NormalizerInterface $normalizer,
    ) {
    }

    public function request(object $request): ResponseInterface
    {
        return $this->httpClient->request(
            'POST',
            $this->getPath($request),
            ['json' => $this->normalizer->normalize($request)],
        );
    }

    private function getPath(object $request): string
    {
        $reflection = new ReflectionClass($request);

        if ([] === $attributes = $reflection->getAttributes(Post::class)) {
            throw new InvalidArgumentException('Post attribute not found.');
        }

        /** @var Post */
        $attribute = $attributes[0]->newInstance();

        return substr($attribute->getUriTemplate(), 1);
    }
}
