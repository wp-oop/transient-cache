# Inpsyde - WP Events

[![Build Status](https://travis-ci.org/wp-oop/transient-cache.svg?branch=develop)](https://travis-ci.org/wp-oop/transient-cache)
[![Latest Stable Version](https://poser.pugx.org/wp-oop/transient-cache/version)](https://packagist.org/packages/wp-oop/transient-cache)

A [PSR-16][] wrapper for WP transients.

## Details
A common means of caching values in WordPress is by using [transients][transients-api]. However, this approach suffers
from several problems:

1. Coupling to WordPress. You can't just suddenly substitute the caching mechanism you are using for another mechanism,
and everything still works.
2. No namespacing. All transients live in the same namespace, and independent consumers cannot reliably use
arbitrary keys without the risk of possible conflict.
3. No true modularity. Due to the above, if your application is [modular][`dhii/module-interface`], it cannot
decide which caching mechanisms to use for what, because that would have already been decided by your modules.
4. Missing features. For example, it is not possible to clear all values related to a particular thing in one go.
Exceptions are missing too, and you have to rely on ambiguous return values.

This standards-compliant wrapper addresses all of the above. It is a true PSR-16 cache, which uses WordPress
transients as storage. Exceptions are raised, interfaces implemented, and true false-negative detection is in place.
Each instance of the cache pool is logically independent from other instances, provided that it is given a unique
name. The application is once again in control, and modules that use cache can become platform agnostic.

### Usage
```php
/*
 * Set up the factory - usually in a service definition
 */
use wpdb;
use Psr\SimpleCache\CacheInterface;
use WpOop\TransientCache\CachePoolFactory;

/* @var $wpdb wpdb */
$factory = new CachePoolFactory($wpdb);


/*
 * Create cache pools - usually somewhere else
 */
// Same wpdb instance used, default value generated automatically
$pool1 = $factory->createCachePool('client-access-tokens');
$pool2 = $factory->createCachePool('remote-api-responses');
$pool3 = $factory->createCachePool('other-stuff');

/*
 * Use cache pools - usually injected into a client class
 */

// No collision of key between different pools
$pool1->set('123', $someToken);
$pool2->set('123', $someResponseBody);
$pool3->set('123', false);

// Depend on an interop standard
(function (CacheInterface $cache) {
    // False negative detection: correctly determines that the value is actually `false`
    $cache->has('123'); // true
    $cache->get('123', uniqid('default')) === false; // true
})($pool3);

// Clear all values within a pool
$pool2->clear();
$pool2->has('123'); // false
$pool1->has('123'); // true
```

[transients-api]: https://codex.wordpress.org/Transients_API
[`dhii/module-interface`]: https://github.com/Dhii/module-interface

[PSR-16]: https://www.php-fig.org/psr/psr-16/ 

