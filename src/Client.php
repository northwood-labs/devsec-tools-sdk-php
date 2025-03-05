<?php
declare(strict_types=1);

namespace DevSecTools;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;

/**
 * Client - A PHP SDK for interacting with the DevSecTools API.
 *
 * This client provides an easy way to interact with the DevSecTools API, which scans websites
 * for security-related information such as domain parsing, HTTP version support, and TLS configurations.
 * It uses Guzzle to handle HTTP requests and supports both synchronous and asynchronous (parallel) requests.
 *
 * ## Usage:
 *
 * ### Instantiating with Default Configuration:
 * ```php
 * use DevSecTools\Client;
 * $api = new Client();
 * ```
 *
 * ### Custom Configuration:
 * ```php
 * $api = new Client([
 *     'base_uri'       => Endpoint::LOCALDEV,
 *     'timeout_seconds' => 10, // Timeout in seconds
 * ]);
 * ```
 *
 * ### Updating Configuration at Runtime:
 * ```php
 * $api->setBaseUri(Endpoint::LOCALDEV);
 * $api->setTimeoutSeconds(15); // Set timeout to 15 seconds
 * ```
 */
final class Client
{
    private GuzzleClient $client;
    private string $baseUri;
    private int $timeoutSeconds;

    /**
     * Initializes the API client with the given configuration.
     *
     * @param array{
     *     base_uri?: string,
     *     timeout_seconds?: int
     * } $config Associative array containing:
     *   - 'base_uri' (string): The API base URL (default: Endpoint::PRODUCTION).
     *   - 'timeout_seconds' (int): The network timeout in seconds (default: 5).
     */
    public function __construct(array $config = [])
    {
        $this->baseUri = $config['base_uri'] ?? Endpoint::PRODUCTION;
        $this->timeoutSeconds = $config['timeout_seconds'] ?? 5;
        $this->initializeClient();
    }

    /**
     * Initializes the Guzzle HTTP client with the current configuration.
     */
    private function initializeClient(): void
    {
        $this->client = new GuzzleClient([
            'base_uri' => $this->baseUri,
            'timeout'  => $this->timeoutSeconds,
            'headers'  => [
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * Makes a GET request to the API.
     *
     * @param string $endpoint The API endpoint (e.g., '/domain', '/http', '/tls').
     * @param array<string, string> $query Query parameters to include in the request.
     *
     * @return array<mixed> The JSON-decoded response from the API.
     *
     * @throws RequestException If the request fails.
     */
    private function request(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->client->get($endpoint, ['query' => $query]);
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Sets the API base URI and reinitializes the client.
     *
     * @param string $baseUri The new base URI.
     */
    public function setBaseUri(string $baseUri): void
    {
        $this->baseUri = $baseUri;
        $this->initializeClient();
    }

    /**
     * Sets the network timeout in seconds and reinitializes the client.
     *
     * @param int $timeoutSeconds The timeout duration in seconds.
     */
    public function setTimeoutSeconds(int $timeoutSeconds): void
    {
        $this->timeoutSeconds = $timeoutSeconds;
        $this->initializeClient();
    }

    /**
     * Retrieves domain information.
     *
     * @param string $url The URL to scan.
     *
     * @return array<mixed> The parsed domain information.
     *
     * @throws RequestException If the request fails.
     */
    public function domain(string $url): array
    {
        return $this->request('/domain', ['url' => $url]);
    }

    /**
     * Retrieves HTTP versions supported by the domain.
     *
     * @param string $url The URL to scan.
     *
     * @return array<mixed> The supported HTTP versions.
     *
     * @throws RequestException If the request fails.
     */
    public function http(string $url): array
    {
        return $this->request('/http', ['url' => $url]);
    }

    /**
     * Retrieves TLS versions and cipher suites supported by the domain.
     *
     * @param string $url The URL to scan.
     *
     * @return array<mixed> The TLS configuration of the domain.
     *
     * @throws RequestException If the request fails.
     */
    public function tls(string $url): array
    {
        return $this->request('/tls', ['url' => $url]);
    }

    /**
     * Executes multiple API requests in parallel.
     *
     * @param array<int, array{method: string, url: string}> $requests
     *        Array of requests where 'method' is the API method (domain, http, tls)
     *        and 'url' is the target domain.
     *
     * @return array<int, array> An array of API responses, indexed by the original request order.
     *
     * @throws RequestException If any of the requests fail.
     */
    public function batch(array $requests): array
    {
        $promises = [];

        foreach ($requests as $key => $req) {
            $endpoint = '/' . trim($req['method'], '/');
            $query = ['query' => ['url' => $req['url']]];
            $promises[$key] = $this->client->getAsync($endpoint, $query);
        }

        $results = Promise\Utils::settle($promises)->wait();

        return array_map(static function ($result): array {
            if ($result['state'] === 'fulfilled') {
                return json_decode($result['value']->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            }
            return ['error' => $result['reason']->getMessage()];
        }, $results);
    }
}
