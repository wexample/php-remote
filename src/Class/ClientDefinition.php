<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Class;

use Wexample\PhpApi\Common\Client;
use Wexample\PhpApi\Common\ClientOptions;

/**
 * What a php-api client is built from. Settings may be empty — an environment
 * variable left unset — and are then reported rather than failing the build.
 */
final readonly class ClientDefinition
{
    /**
     * @param class-string<Client> $class A Client subclass keeping Client's constructor.
     * @param array<string, string|null> $headers
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $class = Client::class,
        public ?string $baseUrl = null,
        public ?string $apiKey = null,
        public bool $apiKeyRequired = false,
        public array $headers = [],
        public ClientOptions $options = new ClientOptions(),
    ) {
    }

    /**
     * @return string[] the settings that resolved empty
     */
    public function getMissingSettings(): array
    {
        $missing = [];

        if (in_array($this->baseUrl, [null, ''], true)) {
            $missing[] = 'base_url';
        }

        if ($this->apiKeyRequired && in_array($this->apiKey, [null, ''], true)) {
            $missing[] = 'api_key';
        }

        return $missing;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return array_filter($this->headers, static fn (?string $value): bool => null !== $value && '' !== $value);
    }
}
