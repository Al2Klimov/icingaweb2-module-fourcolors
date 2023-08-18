<?php

namespace Icinga\Module\Fourcolors\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Fourcolors\Form\ConfirmForm;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

class JoinController extends CompatController
{
    public function indexAction(): void
    {
        $game = $this->params->getRequired('game');

        $this->addContent(
            (new ConfirmForm($this->translate('Join game')))
                ->on(ConfirmForm::ON_SUCCESS, function () use ($game): void {
                    $this->redirectNow(Url::fromPath('fourcolors/lobby')->setParam('game', $game));
                })
                ->handleRequest(ServerRequest::fromGlobals())
        );
    }
}
