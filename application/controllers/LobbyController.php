<?php

namespace Icinga\Module\Fourcolors\Controllers;

use ipl\Html\Html;
use ipl\Web\Compat\CompatController;

class LobbyController extends CompatController
{
    public function indexAction(): void
    {
        $this->params->getRequired('game');

        $this->addContent(Html::tag('h2', $this->translate('Players')));
        $this->addContent(Html::tag('ul', Html::tag('li', $this->Auth()->getUser()->getUsername())));
    }
}
