<?php

namespace Icinga\Module\Fourcolors\Controllers;

use Icinga\Module\Fourcolors\RedisAwareController;
use Icinga\Security\SecurityException;
use ipl\Html\Html;
use ipl\Web\Compat\CompatController;

class PlayController extends CompatController
{
    use RedisAwareController;

    public function indexAction(): void
    {
        $game = $this->params->getRequired('game');
        $state = $this->loadGame($this->getRedis(), $game);

        if (! $state->started) {
            throw new SecurityException($this->translate('Game not started yet: %s'), $game);
        }

        $user = $this->Auth()->getUser()->getUsername();

        if (! array_key_exists($user, $state->players)) {
            throw new SecurityException($this->translate('You haven\'t joined game: %s'), $game);
        }

        $this->addContent(Html::tag('p', (string) $state->lastPlayed));

        $tbody = Html::tag('tbody');

        foreach ($state->players as $player => $cards) {
            $tbody->addHtml(Html::tag('tr', [], [Html::tag('td', $player), Html::tag('td', count($cards))]));
        }

        $this->addContent(Html::tag('table', ['class' => 'common-table'], [
            Html::tag('thead', [], Html::tag('tr', [], [
                Html::tag('th', $this->translate('Player')), Html::tag('th', $this->translate('Cards'))
            ])),
            $tbody
        ]));
    }
}
