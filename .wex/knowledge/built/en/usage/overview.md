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

## Signals

A remote tells what happens to it in one shape, `RemoteSignal`: its key, a kind — `Failure`, `Anomaly`, `Recovery` —, a stable code, a message, a context, and a fingerprint when the code alone does not tell two of them apart.

```php
$reporter = new RemoteReporter($dispatcher, $logger);   // a PSR-14 dispatcher, a PSR-3 logger; both optional

// Something in the answer is wrong, and the remote dealt with it.
$reporter->anomaly('senat_debates', 'misdated_report', 'Report dated after today.', ['file' => 'd20261204.xml'], 'd20261204.xml');

// It failed; then it works again, which closes what the failure opened.
$reporter->failure('dila_an_debates', 'call_failed', 'Timeout.');
$reporter->recovery('dila_an_debates', 'call_failed');
```

The signal itself is the dispatched event: an incident tracker, a history, a counter listens to `RemoteSignal`, and the remote knows none of them. It is also logged — a failure as a warning, an anomaly as a notice —; a recovery, which comes with every call that works, is dispatched only.

The registry signals what its checks find: `Down` as a failure, `Up` as its recovery, both under the code `down` (`RemoteRegistry::CODE_DOWN`).

## A call that may fail

A remote can stop answering, or change its format, at any time. Where the app can do without what it would have given — an import going on with the next item, a page rendered without a block —, run the call through the guard:

```php
$guard = new RemoteGuard($reporter);

$points = $guard->attempt($senat, fn () => $senat->fetchReport($day), []);
```

Any `Exception` the call raises is reported as a failure, code `call_failed` (`RemoteGuard::CODE_CALL_FAILED`), and the fallback is returned; a call that works reports the recovery. A `LogicException` or an `Error` is the app's own bug and goes through. A remote reports an answer it can no longer read with `RemoteException`, which carries its key.
