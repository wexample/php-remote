<?php

declare(strict_types=1);

namespace Wexample\PhpRemote\Tests\Fixtures\Client;

use Wexample\PhpApi\Common\Client;

class DemoApiClient extends Client
{
    public const string PING_PATH = 'health';
}
