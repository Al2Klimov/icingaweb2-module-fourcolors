<?php

namespace Icinga\Module\Fourcolors\Controllers;

use ipl\Html\Html;
use ipl\Web\Compat\CompatController;

class IndexController extends CompatController
{
    public function indexAction(): void
    {
        $this->addContent(Html::tag('p'));
    }
}
