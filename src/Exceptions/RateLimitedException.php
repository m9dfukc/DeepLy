<?php

declare(strict_types=1);

namespace Octfx\DeepLy\Exceptions;

use Exception;

/**
 * Code: 429
 * Thrown if too many requests have been send.
 */
class RateLimitedException extends Exception
{
}
