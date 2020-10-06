<?php

declare(strict_types=1);

namespace Octfx\DeepLy\HttpClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Stream;
use InvalidArgumentException;
use LogicException;
use Octfx\DeepLy\DeepLy;

/**
 * This class uses cURL to execute API calls.
 */
class GuzzleHttpClient implements HttpClientInterface
{
    /**
     * The Guzzle instance.
     *
     * @var Client
     */
    protected $guzzle;

    /**
     * GuzzleHttpClient constructor.
     */
    public function __construct()
    {
        if (!$this->isGuzzleAvailable()) {
            throw new LogicException(
                sprintf(
                    '%s. %s.',
                    'Cannot create instance of Guzzle, because it is not available',
                    'It is not installed or the autoloading is not working'
                )
            );
        }

        $this->guzzle = new Client(
            [
                'base_uri' => DeepLy::API_BASE_URL,
                'timeout' => 10.0,
            ]
        );
    }

    /**
     * Executes an API call (a request) and returns the raw response data.
     *
     * @param string $url     The URL of the API endpoint
     * @param string $method
     * @param array  $payload The payload of the request. Will be encoded as JSON
     *
     * @return string The raw response data as string (usually contains stringified JSON)
     *
     * @throws CallException
     */
    public function callApi(string $url, string $method, array $payload = []): string
    {
        try {
            if ('GET' === $method) {
                $guzzleResponse = $this->guzzle->request(
                    'GET',
                    $url,
                    [
                        'query' => $payload,
                    ]
                );
            } else {
                $guzzleResponse = $this->guzzle->request(
                    'POST',
                    $url,
                    [
                        'form_params' => $payload,
                    ]
                );
            }
        } catch (GuzzleException $exception) {
            throw new CallException(
                sprintf('%s: %s', 'cURL error during DeepLy API call', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }

        $code = $guzzleResponse->getStatusCode();
        if (200 !== $code) {
            // Note that the response probably will contain an error description wrapped in a HTML page
            throw new CallException(
                sprintf('%s: HTTP code %d', 'Server side error during DeepLy API call', $code)
            );
        }

        if ($guzzleResponse->getBody() instanceof Stream) {
            $rawResponseData = $guzzleResponse->getBody()->getContents();
        } else {
            // This should never happen
            throw new CallException('$guzzleResponse->getBody() did not return a Stream object');
        }

        return $rawResponseData;
    }

    /**
     * Returns true if the Guzzle client is available via auto-loading.
     *
     * @return bool
     */
    public function isGuzzleAvailable(): bool
    {
        return class_exists(Client::class);
    }
}
