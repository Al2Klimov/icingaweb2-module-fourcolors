<?php

namespace Icinga\Module\Fourcolors\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Fourcolors\Form\ConfirmForm;
use Icinga\Module\Fourcolors\Game;
use Icinga\Module\Fourcolors\RedisAwareController;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

class IndexController extends CompatController
{
    use RedisAwareController;

    public function indexAction(): void
    {
        $this->addContent(
            (new ConfirmForm($this->translate('New game')))
                ->on(ConfirmForm::ON_SUCCESS, function (): void {
                    $state = new Game();
                    $state->players[$this->Auth()->getUser()->getUsername()] = [];
                    $state = serialize($state);

                    for ($redis = $this->getRedis();;) {
                        $game = uniqid();
                        $key = static::$redisPrefix . "game:$game";

                        $redis->watch($key);

                        if ($redis->get($key) !== null) {
                            continue;
                        }

                        $redis->multi();
                        $redis->set($key, $state);
                        $redis->expire($key, Game::EXPIRE);

                        if ($redis->exec() !== null) {
                            break;
                        }
                    }

                    $this->redirectNow(Url::fromPath('fourcolors/lobby')->setParam('game', $game));
                })
                ->handleRequest(ServerRequest::fromGlobals())
        );
    }
}
