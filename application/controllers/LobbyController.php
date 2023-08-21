<?php

namespace Icinga\Module\Fourcolors\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Fourcolors\Card;
use Icinga\Module\Fourcolors\Form\ConfirmForm;
use Icinga\Module\Fourcolors\Game;
use Icinga\Module\Fourcolors\RedisAwareController;
use Icinga\Security\SecurityException;
use ipl\Html\Html;
use ipl\Html\ValidHtml;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

class LobbyController extends CompatController
{
    use RedisAwareController;

    protected $autorefreshInterval = 1;

    public function indexAction(): void
    {
        $game = $this->params->getRequired('game');
        $redis = $this->getRedis();
        $state = $this->loadGame($redis, $game);

        if ($state->started) {
            $this->redirectNow(Url::fromPath('fourcolors/play')->setParam('game', $game));
        }

        $join = Url::fromPath('fourcolors/join')->setParam('game', $game);

        $this->addContent(Html::tag('h2', $this->translate('Invite others')));
        $this->addContent(Html::tag('p', $this->translate('Right-click the link and select copy link location.')));
        $this->addContent(Html::tag('p', Html::tag('a', ['href' => $join], $join->getAbsoluteUrl())));

        if (count($state->players) > 1) {
            if (array_key_first($state->players) === $this->Auth()->getUser()->getUsername()) {
                $this->addContent(
                    (new ConfirmForm($this->translate('Start game')))
                        ->on(ConfirmForm::ON_SUCCESS, function () use ($redis, $game): void {
                            $this->updateGame($redis, $game, function (Game $state) use ($game): void {
                                if ($state->started) {
                                    throw new SecurityException($this->translate('Game already started: %s'), $game);
                                }

                                $state->started = true;

                                foreach ($state->players as &$cards) {
                                    for ($i = 0; $i < 7; ++$i) {
                                        $cards[] = Card::random();
                                    }
                                }
                                unset($cards);

                                if ($state->lastPlayed->skip) {
                                    $next = array_key_first($state->players);
                                    $cards = $state->players[$next];
                                    unset($state->players[$next]);
                                    $state->players[$next] = $cards;
                                }
                            });

                            $this->redirectNow(Url::fromPath('fourcolors/play')->setParam('game', $game));
                        })
                        ->handleRequest(ServerRequest::fromGlobals())
                );
            }
        }

        $this->addContent(Html::tag('h2', $this->translate('Players')));

        $this->addContent(Html::tag('ul', [], array_map(
            function (string $name): ValidHtml {
                return Html::tag('li', $name);
            },
            array_keys($state->players)
        )));
    }
}
