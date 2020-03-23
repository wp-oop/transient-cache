<?php
declare(strict_types=1);

namespace WpOop\TransientCache\Exception;

use Exception;
use Psr\SimpleCache\CacheException as CacheExceptionInterface;

/**
 * @inheritDoc
 */
class CacheException extends Exception implements CacheExceptionInterface
{
}
