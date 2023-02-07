<?php

declare(strict_types=1);

namespace Manyou\Mango\Utils;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Contracts\Cache\CallbackInterface;

class LockedStore
{
    public function __construct(
        private string $prefix,
        private CacheItemPoolInterface $pool,
        private LockFactory $lockFactory,
    ) {
    }

    /** @param callable|CallbackInterface $callback */
    public function get(string $key, callable $callback): mixed
    {
        $key = $this->prefix . $key;

        $lock = $this->lockFactory->createLock($key);
        if (! $lock->acquire(false)) {
            throw new LockConflictedException('Failed to acquire lock.');
        }

        $item   = $this->pool->getItem($key);
        $save   = ! $item->isHit();
        $delete = false;
        $item->set($callback($item, $save, $delete));
        if ($save) {
            $this->pool->save($item);
        } elseif ($delete && $item->isHit()) {
            $this->pool->deleteItem($key);
        }

        return $item->get();
    }

    public function delete(string $key): void
    {
        $key = $this->prefix . $key;

        $this->pool->deleteItem($key);
    }
}
