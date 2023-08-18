<?php

namespace Icinga\Module\Fourcolors;

class Game
{
    const EXPIRE = 3600;

    public array $players = [];
    public bool $started = false;
    public Card $lastPlayed;

    public function __construct()
    {
        $this->lastPlayed = Card::random();
    }
}
