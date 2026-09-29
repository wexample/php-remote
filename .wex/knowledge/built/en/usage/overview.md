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
