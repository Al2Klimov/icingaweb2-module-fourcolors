<?php

namespace Icinga\Module\Fourcolors;

use Icinga\Exception\NotFoundError;
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

    private function loadGame(Client $redis, string $id): Game
    {
        $game = $redis->get(static::$redisPrefix . "game:$id");

        if ($game === null) {
            throw new NotFoundError($this->translate('No such game: %s'), $id);
        }

        $game = unserialize($game);

        if (! $game instanceof Game) {
            throw new NotFoundError($this->translate('No such game: %s'), $id);
        }

        return $game;
    }

    private function updateGame(Client $redis, string $id, callable $do): void
    {
        for ($key = static::$redisPrefix . "game:$id";;) {
            $redis->watch($key);

            $game = $this->loadGame($redis, $id);

            call_user_func($do, $game);

            $redis->multi();
            $redis->set($key, serialize($game));
            $redis->expire($key, Game::EXPIRE);

            if ($redis->exec() !== null) {
                break;
            }
        }
    }
}
