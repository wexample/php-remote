<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Fixtures\Remote;

use Wexample\PhpRemote\Class\RemoteStatus;
use Wexample\PhpRemote\Interface\RemoteInterface;

class ReachableRemote implements RemoteInterface
{
    public function getKey(): string
    {
        return 'reachable';
    }

    public function getLabel(): string
    {
        return 'Reachable';
    }

    public function checkStatus(): RemoteStatus
    {
        return RemoteStatus::up();
    }
}
