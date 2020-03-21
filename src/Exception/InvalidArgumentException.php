<?php
declare(strict_types=1);

namespace WpOop\TransientCache\Exception;

use InvalidArgumentException as NativeInvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

/**
 * @inheritDoc
 */
class InvalidArgumentException extends NativeInvalidArgumentException implements PsrInvalidArgumentException
{
}
