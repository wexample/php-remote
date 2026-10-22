<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Interface;

use Wexample\PhpRemote\Class\RemoteStatus;

/**
 * Something the app talks to outside itself — an HTTP API, an SDK, a binary —
 * whatever its transport. RemoteRegistry collects them and checks them.
 */
interface RemoteInterface
{
    /**
     * Unique among the app's remotes; what commands and screens name it by.
     */
    public function getKey(): string;

    public function getLabel(): string;

    /**
     * Asks the remote whether it answers. May throw: the registry turns a
     * failure into a Down status, so an implementation need not catch.
     */
    public function checkStatus(): RemoteStatus;
}
