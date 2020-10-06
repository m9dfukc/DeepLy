<?php

declare(strict_types=1);

namespace Octfx\DeepLy\Protocol;

use InvalidArgumentException;
use stdClass;
use function GuzzleHttp\json_decode;

/**
 * Class JsonProtocol.
 */
class JsonProtocol implements ProtocolInterface
{
    /**
     * Processes the data from an response from the server to an API call.
     * Returns the payload (data) of the response or throws a ProtocolException.
     *
     * @param string $rawResponseData The data (payload) of the response as a stringified JSON string
     *
     * @return stdClass|array The data (payload) of the response as an object structure
     *
     * @throws ProtocolException|InvalidArgumentException
     */
    public function processResponseData(string $rawResponseData)
    {
        return json_decode($rawResponseData);
    }
}
