<?php

declare(strict_types=1);

namespace Manyou\Mango\Jose;

use DateInterval;
use Jose\Component\KeyManagement\JKUFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedJWKSLoader implements JWKSLoader
{
    public function __construct(
        private JKUFactory $jkuFactory,
        private CacheInterface $cache,
        #[Autowire('%env(SL_JOSE_BRIDGE_JWKS_URI)%')]
        private string $url,
        private array $header = [],
        private int|DateInterval|null $expiresAfter = 120,
    ) {
    }

    public function __invoke(): array
    {
        return $this->cache->get(
            $this->url,
            function (ItemInterface $item) {
                $item->expiresAfter($this->expiresAfter);

                return $this->jkuFactory->loadFromUrl($this->url, $this->header)->all();
            },
        );
    }
}
