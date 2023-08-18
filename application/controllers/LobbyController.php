<?php

namespace Icinga\Module\Fourcolors\Controllers;

use ipl\Html\Html;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;

class LobbyController extends CompatController
{
    public function indexAction(): void
    {
        $join = Url::fromPath('fourcolors/join')->setParam('game', $this->params->getRequired('game'));

        $this->addContent(Html::tag('h2', $this->translate('Invite others')));
        $this->addContent(Html::tag('p', $this->translate('Right-click the link and select copy link location.')));
        $this->addContent(Html::tag('p', Html::tag('a', ['href' => $join], $join->getAbsoluteUrl())));

        $this->addContent(Html::tag('h2', $this->translate('Players')));
        $this->addContent(Html::tag('ul', Html::tag('li', $this->Auth()->getUser()->getUsername())));
    }
}
