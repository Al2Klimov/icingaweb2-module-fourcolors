<?php

// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Icinga\Module\Fourcolors\Form;

use Icinga\Web\Session;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Compat\CompatForm;

class ConfirmForm extends CompatForm
{
    use CsrfCounterMeasure;

    protected string $label;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    protected function assemble(): void
    {
        $this->addElement('submit', 'btn_submit', ['label' => $this->label]);
        $this->addElement($this->createCsrfCounterMeasure(Session::getSession()->getId()));
    }
}
