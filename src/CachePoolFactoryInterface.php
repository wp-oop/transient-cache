<?php
declare(strict_types=1);

namespace WpOop\TransientCache;

use Psr\SimpleCache\CacheInterface;

/**
 * A factory that can create cache pool.
 */
interface CachePoolFactoryInterface
{
    /**
     * Creates a new cache pool.
     *
     * @param string $poolName The unique pool name.
     *
     * @return CacheInterface The new pool.
     */
    public function createCachePool(string $poolName): CacheInterface;
}
