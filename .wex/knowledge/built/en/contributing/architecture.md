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
