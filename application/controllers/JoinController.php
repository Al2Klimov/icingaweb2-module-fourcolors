<?php

namespace Icinga\Module\Fourcolors\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Fourcolors\Form\ConfirmForm;
use Icinga\Module\Fourcolors\Game;
use Icinga\Module\Fourcolors\RedisAwareController;
use Icinga\Security\SecurityException;
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
                    $this->updateGame($this->getRedis(), $game, function (Game $state) use ($game): void {
                        if ($state->started) {
                            throw new SecurityException($this->translate('Game already started: %s'), $game);
                        }

                        $state->players[$this->Auth()->getUser()->getUsername()] = [];
                    });

                    $this->redirectNow(Url::fromPath('fourcolors/lobby')->setParam('game', $game));
                })
                ->handleRequest(ServerRequest::fromGlobals())
        );
    }
}
