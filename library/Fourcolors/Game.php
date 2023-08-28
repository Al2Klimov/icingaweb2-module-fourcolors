<?php

// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Icinga\Module\Fourcolors;

class Game
{
    const EXPIRE = 3600;

    public array $players = [];
    public bool $started = false;
    public Card $lastPlayed;
    public int $draw = 0;
    public bool $drawn = false;

    public function __construct()
    {
        $this->lastPlayed = Card::random();
    }
}
