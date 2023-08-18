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

        $this->addContent(Html::tag('p'));
    }
}
