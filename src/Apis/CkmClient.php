<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Apis;

use GuzzleHttp\Client;
use GuzzleHttp\ClientTrait;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class CkmClient
{

    use ClientTrait;

    protected readonly Client $client;

    public function __construct(
        protected readonly LoggerInterface $logger,
        ?Client $client = null,
    )
    {
        if ($client !== null) {
            $this->client = $client;
            $this->logger->info('CKM API client injected.');
            return;
        }
        $apiConfig = [
            'base_uri' => CKM_API_BASE_URL,
            RequestOptions::VERIFY => HTTP_SSL_VERIFY,
            RequestOptions::TIMEOUT => max(HTTP_TIMEOUT, 5.0),
        ];
        $this->client = new Client($apiConfig);
        $this->logger->info('CKM API client built.', $apiConfig);
    }

    /**
     * @param string $method
     * @param $uri
     * @param array<string,mixed> $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $uri, $options);
    }

    /**
     * @param string $method
     * @param $uri
     * @param array<string,mixed> $options
     * @return PromiseInterface
     */
    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return $this->client->requestAsync($method, $uri, $options);
    }

}
