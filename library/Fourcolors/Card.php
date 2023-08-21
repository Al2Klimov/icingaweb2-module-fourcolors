<?php

namespace Icinga\Module\Fourcolors;

use ipl\I18n\Translation;

class Card
{
    use Translation;

    public static array $colors = ['♠', '♥', '♣', '♦'];

    public ?string $color = null;
    public ?int $number = null;
    public bool $skip = false;
    public bool $reverse = false;
    public bool $choose = false;
    public int $draw = 0;

    public static function random(): self
    {
        $regular = 19;
        $skip = 2;
        $reverse = 2;
        $draw2 = 2;
        $coloredEach = $regular + $skip + $reverse + $draw2;
        $colored = count(static::$colors) * $coloredEach;
        $draw4 = 4;
        $wild = 4;
        $total = $colored + $draw4 + $wild;
        $dice = mt_rand(0, $total - 1);
        $card = new static();

        if ($dice < $colored) {
            $card->color = static::$colors[$dice % count(static::$colors)];
            $dice = intdiv($dice, count(static::$colors));

            if ($dice < $regular) {
                $card->number = ($dice + 1) % 10;
            } elseif ($dice < $regular + $skip) {
                $card->skip = true;
            } elseif ($dice < $regular + $skip + $reverse) {
                $card->reverse = true;
            } else {
                $card->draw = 2;
            }
        } else {
            $card->choose = true;

            if ($dice < $colored + $draw4) {
                $card->draw = 4;
            }
        }

        return $card;
    }

    public function playableOn(self $card): bool
    {
        return $this->color === null || $card->color === null || $this->color === $card->color
            || $this->number !== null && $this->number === $card->number
            || $this->skip && $card->skip
            || $this->reverse && $card->reverse
            || $this->draw === 2 && $card->draw === 2;
    }

    public function __toString()
    {
        $parts = [];

        if ($this->color !== null) {
            $parts[] = $this->color;
        }

        if ($this->number !== null) {
            $parts[] = $this->number;
        }

        if ($this->skip) {
            $parts[] = $this->translate('skip next player');
        }

        if ($this->reverse) {
            $parts[] = $this->translate('reverse order');
        }

        if ($this->choose) {
            $parts[] = $this->translate('choose color');
        }

        if ($this->draw > 0) {
            $parts[] = sprintf($this->translate('next player draws %d'), $this->draw);
        }

        return implode(' | ', $parts);
    }
}
