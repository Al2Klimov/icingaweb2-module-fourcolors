<?php

// SPDX-License-Identifier: AGPL-3.0-or-later

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

        $this->addContent(
            Html::tag('p', sprintf($this->translate('It\'s %s\'s turn.'), array_key_first($state->players)))
        );

        if (array_key_first($state->players) === $user) {
            $this->addContent(
                (new ActionForm())
                    ->setGame($state)
                    ->on(ActionForm::ON_SUCCESS, function (ActionForm $form) use ($redis, $game, $user): void {
                        $this->autorefreshInterval = 1;

                        $this->updateGame($redis, $game, function (Game $state) use ($user, $form): void {
                            $action = $form->getValue('action');
                            $state->drawn = false;

                            switch ($action) {
                                case ActionForm::DRAW:
                                    $draw = $state->draw > 0 ? $state->draw : 1;

                                    if ($state->draw === 0) {
                                        $state->drawn = true;
                                    }

                                    $state->draw = 0;

                                    for ($i = 0; $i < $draw; ++$i) {
                                        $state->players[$user][] = Card::random();
                                    }
                                    break;

                                case ActionForm::DO_NOTHING:
                                    $cards = $state->players[$user];
                                    unset($state->players[$user]);
                                    $state->players[$user] = $cards;
                                    break;

                                default:
                                    if (! isset($state->players[$user][$action])) {
                                        throw new SecurityException(
                                            $this->translate('No such card index: %s'),
                                            $action
                                        );
                                    }

                                    if (! $state->players[$user][$action]->playableOn($state->lastPlayed)) {
                                        throw new SecurityException(
                                            $this->translate('Illegal card: %s'),
                                            (string) $state->players[$user][$action]
                                        );
                                    }

                                    $state->lastPlayed = $state->players[$user][$action];
                                    unset($state->players[$user][$action]);

                                    if ((count($state->players[$user]) === 1) !== ($form->getValue('uno') === 'y')) {
                                        for ($i = 0; $i < 4; ++$i) {
                                            $state->players[$user][] = Card::random();
                                        }
                                    }

                                    if ($state->lastPlayed->choose) {
                                        $state->lastPlayed->color = Card::$colors[$form->getValue('color')];
                                    }

                                    $cards = $state->players[$user];
                                    unset($state->players[$user]);

                                    if ($state->lastPlayed->reverse) {
                                        $state->players = array_reverse($state->players, true);
                                    }

                                    $state->players[$user] = $cards;

                                    if ($state->lastPlayed->skip) {
                                        $next = array_key_first($state->players);
                                        $cards = $state->players[$next];
                                        unset($state->players[$next]);
                                        $state->players[$next] = $cards;
                                    }

                                    if ($state->lastPlayed->draw > 0) {
                                        $state->draw += $state->lastPlayed->draw;
                                    }
                            }
                        });
                    })
                    ->handleRequest(ServerRequest::fromGlobals())
            );
        } else {
            $this->autorefreshInterval = 1;
        }

        $this->addContent(Html::tag('h2', $this->translate('Discard pile')));
        $this->addContent(Html::tag('p', $this->renderHand($state->lastPlayed)));
        $this->addContent(Html::tag('h2', $this->translate('My cards')));

        $hand = [];
        foreach ($state->players[$user] as $_ => $card) {
            $hand[] = $this->renderHand($card);
        }
        $this->addContent(Html::tag('div', ['class' => 'hand'], $hand));

        $tbody = Html::tag('tbody');

        foreach ($state->players as $player => $cards) {
            $tbody->addHtml(Html::tag('tr', [], [Html::tag('td', $player), Html::tag('td', count($cards))]));
        }

        $this->addContent(Html::tag('h2', $this->translate('All players')));

        $this->addContent(Html::tag('table', ['class' => 'common-table'], [
            Html::tag('thead', [], Html::tag('tr', [], [
                Html::tag('th', $this->translate('Player')), Html::tag('th', $this->translate('Cards'))
            ])),
            $tbody
        ]));
    }
    public function renderHand(Card $card = null) {
        $cardColor = '.';
        switch ($card->color) {
            case '♠':
                $cardColor = 'spades';
                break;
            case '♥':
                $cardColor = 'hearts';
                break;
            case '♣':
                $cardColor = 'clubs';
                break;
            case '♦':
                $cardColor = 'diamonds';
                break;
        }

        $cardNumber = '';
        if ($card->number !== null) {
            $cardNumber = $card->number;
        } elseif ($card->skip) {
            $cardNumber = 'ø';
        } elseif ($card->reverse) {
            $cardNumber = '↔';
        } elseif ($card->draw !== 0) {
            $cardNumber = '+' . $card->draw;
        } elseif ($card->choose) {
            $cardNumber = '┉';
        }

        return Html::tag(
            'div',
            ['class' => "card-container $cardColor"],
            [
                Html::tag('p', ['class' => 'card-color ' . $cardColor], $card->color?$card->color:'.'),
                Html::tag('p', ['class' => 'card-number'], $cardNumber),
            ]
        );
    }
}
