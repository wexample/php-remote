# php-remote

Version: 1.0.2

## A remote

```php
final class RenderServerRemote implements RemoteInterface
{
    public function getKey(): string { return 'render_server'; }

    public function getLabel(): string { return 'Render server'; }

    public function checkStatus(): RemoteStatus
    {
        return $this->renderer->ping() ? RemoteStatus::up() : RemoteStatus::down('No answer.');
    }
}
```

A check may throw instead of returning `down()`: the registry reports the exception message. It also measures the latency, so a remote only says whether it answers.

## The registry

```php
$registry = new RemoteRegistry([$renderServer, $billing]);

$registry->check('billing');   // RemoteStatus
$registry->checkAll();         // array<string, RemoteStatus>, by key
```

Two remotes sharing a key are refused when the registry is built. `RemoteStatus::toArray()` gives the shape to report a status in.

## A php-api client

```php
$definition = new ClientDefinition(
    key: 'billing',
    label: 'Billing API',
    class: BillingClient::class,          // a php-api Client subclass; defaults to Client
    baseUrl: getenv('BILLING_API_URL') ?: null,
    apiKey: getenv('BILLING_API_KEY') ?: null,
    apiKeyRequired: true,
    headers: ['X-Tenant' => 'acme'],
    options: new ClientOptions(timeout: 30.0, retries: 2),
);

$client = (new ApiClientFactory())->create($definition, $rateLimiter);
$remote = new ApiClientRemote($definition, $client);
```

The class must keep the constructor of php-api's `Client`. An empty header is left out. The remote reads `Unconfigured` while the base URL, or a required key, is empty; otherwise its check requests the client's `PING_PATH`.

`$rateLimiter` is optional: any `RateLimiterInterface`, whose `acquire()` returns once a request may go. Backing it with a store shared by every process of the app is the framework's part.

## Table of Contents

- [A remote](#a-remote)
- [The registry](#the-registry)
- [A php-api client](#a-php-api-client)
- [Architecture](#architecture)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Architecture

Everything lives under `Wexample\PhpRemote\` (PSR-4 on `src/`): `Interface/` the contracts, `Class/` the values, the registry and the php-api adapters, `Enum/` the states. The package depends on `wexample/php-api` and Guzzle, and on no framework: `symfony-remote` is the Symfony integration, and a Python counterpart would sit at this same level.

### Remotes and the registry

src/Interface/RemoteInterface.php is the whole contract: a key, a label, `checkStatus()`. src/Class/RemoteRegistry.php takes any iterable of remotes, indexes them by key — two remotes sharing a key throw — and checks them. `check()` wraps the remote's own check: it measures the latency with `hrtime()` and turns any `Throwable` into `RemoteStatus::down($message)`, because a status page must survive the one remote that breaks. That is the only place the package catches everything.

src/Class/RemoteStatus.php is a readonly value: a src/Enum/RemoteState.php (`Up`, `Down`, `Unconfigured`), a message, the latency in milliseconds and the check date.

### php-api clients

src/Class/ClientDefinition.php is what a client is built from, its values possibly empty. It answers `getMissingSettings()` (an empty base URL, or a required key resolving empty) and carries php-api's `ClientOptions` as is.

src/Class/ApiClientFactory.php builds the Guzzle client itself, on a given handler stack or a fresh one, so it can push src/Class/RateLimitMiddleware.php on it, then calls the definition's class with php-api `Client`'s constructor. The middleware sits in the Guzzle stack rather than in a `Client` subclass, so it works for any client class.

src/Class/ApiClientRemote.php is the remote of such a client. Its check reports `Unconfigured` for missing settings, and otherwise requests the client's `PING_PATH`: an error raises php-api's `ApiException`, which the registry reports with its message — more useful than `checkConnection()`'s boolean.

### Rate limiting

src/Interface/RateLimiterInterface.php is one method, `acquire()`, blocking until a request may go. The package ships no implementation: a quota worth having counts across processes, which needs a shared store, and the store is the framework's — `symfony-remote` adapts Symfony's rate limiter. php-api's own `rate_limit_delay` spaces the requests of one client instance only.

### Tests

Unit tests only, under `tests/Unit/`. The client tests build the factory on a Guzzle `MockHandler`, so no request leaves the process; `tests/Fixtures/` holds two remotes (one answering, one throwing), a client with a `PING_PATH` and a rate limiter counting its calls.

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5
- guzzlehttp/guzzle: ^7.8
- wexample/php-api: >=5.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
