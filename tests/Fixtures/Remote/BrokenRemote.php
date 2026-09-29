<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Fixtures\Remote;

use RuntimeException;
use Wexample\PhpRemote\Class\RemoteStatus;
use Wexample\PhpRemote\Interface\RemoteInterface;

class BrokenRemote implements RemoteInterface
{
    public function getKey(): string
    {
        return 'broken';
    }

    public function getLabel(): string
    {
        return 'Broken';
    }

    public function checkStatus(): RemoteStatus
    {
        throw new RuntimeException('Connection refused');
    }
}
