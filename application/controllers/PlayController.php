<?php

namespace Icinga\Module\Fourcolors\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Fourcolors\Card;
use Icinga\Module\Fourcolors\Form\ActionForm;
use Icinga\Module\Fourcolors\Game;
use Icinga\Module\Fourcolors\RedisAwareController;
use Icinga\Security\SecurityException;
use ipl\Html\Html;
use ipl\Html\ValidHtml;
use ipl\Web\Compat\CompatController;

class PlayController extends CompatController
{
    use RedisAwareController;

    public function indexAction(): void
    {
        $game = $this->params->getRequired('game');
        $redis = $this->getRedis();
        $state = $this->loadGame($redis, $game);

        if (! $state->started) {
            throw new SecurityException($this->translate('Game not started yet: %s'), $game);
        }

        $user = $this->Auth()->getUser()->getUsername();

        if (! array_key_exists($user, $state->players)) {
            throw new SecurityException($this->translate('You haven\'t joined game: %s'), $game);
        }

        foreach ($state->players as $player => $cards) {
            if (empty($cards)) {
                $this->addContent(Html::tag('p', sprintf($this->translate('%s won the game.'), $player)));

                return;
            }
        }

        if (array_key_first($state->players) === $user) {
            $this->addContent(
                (new ActionForm())
                    ->setGame($state)
                    ->on(ActionForm::ON_SUCCESS, function (ActionForm $form) use ($redis, $game, $user): void {
                        $this->autorefreshInterval = 1;

                        $this->updateGame($redis, $game, function (Game $state) use ($user, $form): void {
                            $action = $form->getValue('action');

                            if ($action === ActionForm::DRAW) {
                                $state->players[$user][] = Card::random();
                            } else {
                                if (! isset($state->players[$user][$action])) {
                                    throw new SecurityException($this->translate('No such card index: %s'), $action);
                                }

                                if (! $state->players[$user][$action]->playableOn($state->lastPlayed)) {
                                    throw new SecurityException(
                                        $this->translate('Illegal card: %s'),
                                        (string) $state->players[$user][$action]
                                    );
                                }

                                $state->lastPlayed = $state->players[$user][$action];
                                unset($state->players[$user][$action]);

                                $cards = $state->players[$user];
                                unset($state->players[$user]);
                                $state->players[$user] = $cards;
                            }
                        });
                    })
                    ->handleRequest(ServerRequest::fromGlobals())
            );
        } else {
            $this->autorefreshInterval = 1;
        }

        $this->addContent(Html::tag('h2', $this->translate('Discard pile')));
        $this->addContent(Html::tag('p', (string) $state->lastPlayed));
        $this->addContent(Html::tag('h2', $this->translate('My cards')));

        $this->addContent(Html::tag('ul', [], array_map(
            function (Card $card): ValidHtml {
                return Html::tag('li', (string) $card);
            },
            $state->players[$user]
        )));

        $tbody = Html::tag('tbody');

        foreach ($state->players as $player => $cards) {
            $tbody->addHtml(Html::tag('tr', [], [Html::tag('td', $player), Html::tag('td', count($cards))]));
        }

        $this->addContent(Html::tag('h2', $this->translate('Others')));

        $this->addContent(Html::tag('table', ['class' => 'common-table'], [
            Html::tag('thead', [], Html::tag('tr', [], [
                Html::tag('th', $this->translate('Player')), Html::tag('th', $this->translate('Cards'))
            ])),
            $tbody
        ]));
    }
}
