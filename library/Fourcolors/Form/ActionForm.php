<?php

namespace Icinga\Module\Fourcolors\Form;

use Icinga\Authentication\Auth;
use Icinga\Module\Fourcolors\Card;
use Icinga\Module\Fourcolors\Game;
use Icinga\Web\Session;
use ipl\I18n\Translation;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Compat\CompatForm;

class ActionForm extends CompatForm
{
    use CsrfCounterMeasure;
    use Translation;

    const DRAW = 'draw';

    protected ?Game $game = null;

    protected function assemble(): void
    {
        $opts = [static::DRAW => $this->translate('Draw one')];
        $user = Auth::getInstance()->getUser()->getUsername();

        foreach ($this->game->players[$user] as $i => $card) {
            if ($card->playableOn($this->game->lastPlayed)) {
                $opts[$i] = (string) $card;
            }
        }

        $this->addElement('select', 'action', [
            'label'    => $this->translate('Action'),
            'options'  => $opts,
            'required' => true,
            'class'    => ['autosubmit']
        ]);

        $action = $this->getPopulatedValue('action');

        if ($action !== null && $action !== static::DRAW && isset($this->game->players[$user][$action])) {
            if ($this->game->players[$user][$action]->choose) {
                $this->addElement('select', 'color', [
                    'label'    => $this->translate('Color'),
                    'options'  => Card::$colors,
                    'required' => true
                ]);
            }
        }

        $this->addElement('submit', 'btn_submit', ['label' => $this->translate('Proceed')]);
        $this->addElement($this->createCsrfCounterMeasure(Session::getSession()->getId()));
    }

    public function setGame(Game $game): self
    {
        $this->game = $game;

        return $this;
    }
}
