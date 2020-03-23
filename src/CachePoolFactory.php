<?php
declare(strict_types=1);

namespace WpOop\TransientCache;

use Psr\SimpleCache\CacheInterface;
use wpdb;

/**
 * @inheritDoc
 */
class CachePoolFactory implements CachePoolFactoryInterface
{
    /**
     * @var wpdb
     */
    protected $wpdb;

    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * @inheritDoc
     */
    public function createCachePool(string $poolName): CacheInterface
    {
        $default = uniqid('default');
        $pool = new CachePool($this->wpdb, $poolName, $default);

        return $pool;
    }
}
