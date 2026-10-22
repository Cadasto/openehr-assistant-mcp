<?php

declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Apis;

use GuzzleHttp\Client;
use GuzzleHttp\ClientTrait;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * HTTP boundary to the openEHR Archetype Designer server API (ADR-0008).
 *
 * Authenticates with HTTP Basic using a Designer *local* account, and sends the
 * `Repository-Token` header (e.g. a GitHub token for a GitHub-hosted repository)
 * on repository-scoped calls. All settings come from the environment; nothing is
 * hard-coded, and no credential is ever logged.
 */
class ArchetypeDesignerClient
{

    use ClientTrait;

    protected readonly Client $client;

    public function __construct(
        protected readonly LoggerInterface $logger,
        ?Client $client = null,
        private readonly string $username = ARCHETYPE_DESIGNER_USERNAME,
        private readonly string $password = ARCHETYPE_DESIGNER_PASSWORD,
        private readonly string $repositoryId = ARCHETYPE_DESIGNER_REPOSITORY_ID,
        private readonly string $repositoryToken = ARCHETYPE_DESIGNER_REPOSITORY_TOKEN,
    )
    {
        if ($client !== null) {
            $this->client = $client;
            $this->logger->info('Archetype Designer API client injected.');
            return;
        }
        $apiConfig = [
            'base_uri' => ARCHETYPE_DESIGNER_BASE_URL,
            RequestOptions::VERIFY => HTTP_SSL_VERIFY,
            RequestOptions::TIMEOUT => max(HTTP_TIMEOUT, 10.0),
        ];
        // Logged before credentials are attached, so they never reach the log.
        $this->logger->info('Archetype Designer API client built.', $apiConfig + ['configured' => $this->isConfigured()]);
        if ($this->isConfigured()) {
            $apiConfig[RequestOptions::AUTH] = [$this->username, $this->password];
        }
        $this->client = new Client($apiConfig);
    }

    /** Whether Designer credentials are set; without them every call is rejected upstream. */
    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->password !== '';
    }

    /** The repository used when a tool call names none; '' when unset. */
    public function defaultRepositoryId(): string
    {
        return $this->repositoryId;
    }

    /**
     * Headers for repository-scoped calls: `Repository-Token` when one is configured.
     *
     * @return array<string, string>
     */
    public function repositoryHeaders(): array
    {
        return $this->repositoryToken !== '' ? ['Repository-Token' => $this->repositoryToken] : [];
    }

    /**
     * @param array<string,mixed> $options
     * @throws ClientExceptionInterface
     */
    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $uri, $options);
    }

    /**
     * @param array<string,mixed> $options
     */
    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return $this->client->requestAsync($method, $uri, $options);
    }

}
