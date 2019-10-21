<?php

declare(strict_types=1);

namespace Octfx\DeepLy\Protocol;

use InvalidArgumentException;
use stdClass;

/**
 * A class that implements this interface represents the protocol used for communication with the API.
 */
interface ProtocolInterface
{
    /**
     * Processes the data from an response from the server to an API call.
     * Returns the payload (data) of the response or throws a ProtocolException.
     *
     * @param string $rawResponseData The data (payload) of the response as a stringified JSON string
     *
     * @return stdClass The data (payload) of the response as an object structure
     *
     * @throws ProtocolException|InvalidArgumentException
     */
    public function processResponseData($rawResponseData): stdClass;
}
