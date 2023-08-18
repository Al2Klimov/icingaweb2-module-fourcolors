<?php

namespace Icinga\Module\Fourcolors;

use ipl\Web\Compat\CompatController;
use Predis\Client;

trait RedisAwareController
{
    private static string $redisPrefix = '{github.com/Al2Klimov/icingaweb2-module-fourcolors#v1}';

    private function getRedis(): Client
    {
        /** @var CompatController $this */
        return new Client(['host' => $this->Config()->get('redis', 'host', 'localhost')]);
    }
}
