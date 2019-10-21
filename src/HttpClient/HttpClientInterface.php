<?php

declare(strict_types=1);

namespace Octfx\DeepLy\HttpClient;

/**
 * A class that implements the HttpClientInterface allows the DeepLy library
 * to communicate with the API server. Basically it is responsible for API
 * calls from the client to the API server. It does not have to do anything
 * with the result except returning it.
 */
interface HttpClientInterface
{
    /**
     * Executes an API call (a request) and returns the raw response data.
     *
     * @param string $url     The URL of the API endpoint
     * @param string $method  Request Method
     * @param array  $payload The payload of the request. Will be encoded as JSON
     *
     * @return string The raw response data as string (usually contains stringified JSON)
     *
     * @throws CallException Throws a call exception if the call could not be executed
     */
    public function callApi(string $url, string $method, array $payload = []): string;
}
