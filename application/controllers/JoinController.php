<?php

namespace Icinga\Module\Fourcolors\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Fourcolors\Form\ConfirmForm;
use Icinga\Module\Fourcolors\Game;
use Icinga\Module\Fourcolors\RedisAwareController;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

class JoinController extends CompatController
{
    use RedisAwareController;

    public function indexAction(): void
    {
        $game = $this->params->getRequired('game');

        $this->addContent(
            (new ConfirmForm($this->translate('Join game')))
                ->on(ConfirmForm::ON_SUCCESS, function () use ($game): void {
                    $key = static::$redisPrefix . "game:$game";

                    for ($redis = $this->getRedis();;) {
                        $redis->watch($key);

                        $state = $this->loadGame($redis, $game);
                        $state->players[$this->Auth()->getUser()->getUsername()] = null;

                        $redis->multi();
                        $redis->set($key, serialize($state));
                        $redis->expire($key, Game::EXPIRE);

                        if ($redis->exec() !== null) {
                            $this->redirectNow(Url::fromPath('fourcolors/lobby')->setParam('game', $game));
                        }
                    }
                })
                ->handleRequest(ServerRequest::fromGlobals())
        );
    }
}
