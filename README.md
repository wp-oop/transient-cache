# WP Transient Cache

[![Build Status](https://travis-ci.com/wp-oop/transient-cache.svg?branch=develop)](https://travis-ci.org/wp-oop/transient-cache)
[![Latest Stable Version](https://poser.pugx.org/wp-oop/transient-cache/version)](https://packagist.org/packages/wp-oop/transient-cache)
[![Latest Unstable Version](https://poser.pugx.org/wp-oop/transient-cache/v/unstable)](//packagist.org/packages/wp-oop/transient-cache)

A fully compliant [PSR-16][] wrapper for WP transients.

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

### Compatibility
- `CachePool` and `CachePoolFactory` offer best-practices error handling, throwing meaningful exceptions
when something goes wrong. This violates PSR-16, but allows you to know what is failing.
- `SilentPool` and `SilentPoolFactory` offer PSR-16 compatibility at the cost of error handling,
hiding exceptions, and returning standards-compatible values. This complies with PSR-16, but at the cost of
clarity and verbosity.

### Usage
```php
/*
 * Set up the factory - usually in a service definition
 */
use wpdb;
use Psr\SimpleCache\CacheInterface;
use WpOop\TransientCache\CachePoolFactory;
use WpOop\TransientCache\SilentPoolFactory;

/* @var $wpdb wpdb */
$factory = new CachePoolFactory($wpdb);
// Optionally hide exceptions for PSR-16 compatibility
$factory = new SilentPoolFactory($factory); // Optional, and not recommended for testing environments!

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

### Limitations
#### Key Length
Due to the way the underlying backend (the WordPress transients via options) works, **the combined length of the
pool name and cache key MUST NOT exceed a 171 char limit**. This is because (at least in WP 5.0+)
the [length of the `option_name` field of the `options` table is 191 chars][1], and transients require the longest
prefix of `_transient_timeout_` to the option name, which together with the 1-char separator is 20 chars. Using
anything greater than this length will result in potentially devastating behaviour described in [Trac #15058][].

In any case, the general recommendation is that **consumers SHOULD NOT use cache keys longer than 64 chars**,
as this is the minimal length required for support by the PSR-16 spec. Using anything longer than that will
cause consumers to become dependent on implementation detail, which breaks interoperability.
Given that, **the cache pool name SHOULD NOT exceed 107 chars**.

#### Value Length
The storage backend (WP options) [declares][2] the corresponding field to be of type [`LONGTEXT`][], which
[allows][3] up to **4 GB** (2<sup>32</sup>) of data. This is therefore the limit on cache values. 


[transients-api]: https://codex.wordpress.org/Transients_API
[`dhii/module-interface`]: https://github.com/Dhii/module-interface

[PSR-16]: https://www.php-fig.org/psr/psr-16/
[`LONGTEXT`]: https://dev.mysql.com/doc/refman/8.0/en/blob.html

[1]: https://github.com/WordPress/WordPress/blob/5.0-branch/wp-admin/includes/schema.php#L142
[2]: https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/schema.php#L144
[3]: https://dev.mysql.com/doc/refman/8.0/en/storage-requirements.html#data-types-storage-reqs-strings
[Trac #15058]: https://core.trac.wordpress.org/ticket/15058
