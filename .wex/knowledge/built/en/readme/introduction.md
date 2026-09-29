`php-remote` keeps track of everything a PHP application talks to outside itself — an HTTP API, an SDK, a binary — whatever the framework around it. It holds what those have in common: a registry of the app's remotes and a status per remote (up, down, or unconfigured when its address or credentials are missing), with the time the check took.

It is not a transport. Anything implementing `RemoteInterface` is a remote, whatever library it talks through. For HTTP APIs built on `wexample/php-api`, it also builds the client from a plain definition — base URL, key, headers, `ClientOptions` — and can hold each request back until a rate limiter grants it.

The framework integration lives elsewhere: `symfony-remote` registers remotes through autoconfiguration, declares clients in YAML, backs the rate limiter with Symfony's and adds the `remote:status` command.
