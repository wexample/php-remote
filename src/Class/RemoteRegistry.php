<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Class;

use LogicException;
use Throwable;
use Wexample\PhpRemote\Enum\RemoteState;
use Wexample\PhpRemote\Interface\RemoteInterface;

class RemoteRegistry
{
    /**
     * @var array<string, RemoteInterface>
     */
    private readonly array $remotes;

    public const string CODE_DOWN = 'down';

    /**
     * @param iterable<RemoteInterface> $remotes
     */
    public function __construct(
        iterable $remotes,
        private readonly RemoteReporter $reporter = new RemoteReporter(),
    ) {
        $byKey = [];
        foreach ($remotes as $remote) {
            $key = $remote->getKey();

            if (isset($byKey[$key])) {
                throw new LogicException(sprintf(
                    'Two remotes share the key "%s": %s and %s.',
                    $key,
                    $byKey[$key]::class,
                    $remote::class
                ));
            }

            $byKey[$key] = $remote;
        }

        $this->remotes = $byKey;
    }

    /**
     * @return array<string, RemoteInterface>
     */
    public function all(): array
    {
        return $this->remotes;
    }

    public function get(string $key): RemoteInterface
    {
        return $this->remotes[$key] ?? throw new LogicException(sprintf(
            'No remote "%s". Registered: %s.',
            $key,
            implode(', ', array_keys($this->remotes)) ?: 'none'
        ));
    }

    /**
     * Times the remote's own check. A remote that throws is reported Down with
     * the exception message: one broken remote must not hide the others.
     *
     * A Down status is signalled as a failure (`down`), an Up one as its recovery;
     * Unconfigured is the app's configuration, not the remote's doing, and signals
     * nothing.
     */
    public function check(string $key): RemoteStatus
    {
        $remote = $this->get($key);
        $start = hrtime(true);

        try {
            $status = $remote->checkStatus();
        } catch (Throwable $exception) {
            $status = RemoteStatus::down($exception->getMessage());
        }

        match ($status->state) {
            RemoteState::Down => $this->reporter->failure($key, self::CODE_DOWN, (string) $status->message),
            RemoteState::Up => $this->reporter->recovery($key, self::CODE_DOWN),
            RemoteState::Unconfigured => null,
        };

        return $status->withLatency((hrtime(true) - $start) / 1_000_000);
    }

    /**
     * @return array<string, RemoteStatus>
     */
    public function checkAll(): array
    {
        $statuses = [];

        foreach (array_keys($this->remotes) as $key) {
            $statuses[$key] = $this->check($key);
        }

        return $statuses;
    }
}
