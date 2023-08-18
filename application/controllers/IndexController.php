<?php

namespace Icinga\Module\Fourcolors\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Fourcolors\Form\ConfirmForm;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

class IndexController extends CompatController
{
    public function indexAction(): void
    {
        $this->addContent(
            (new ConfirmForm($this->translate('New game')))
                ->on(ConfirmForm::ON_SUCCESS, function (): void {
                    $this->redirectNow(Url::fromPath('fourcolors/lobby')->setParam('game', uniqid()));
                })
                ->handleRequest(ServerRequest::fromGlobals())
        );
    }
}
