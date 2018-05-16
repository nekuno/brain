<?php

namespace Model\Link;

class Game extends Link
{
    const GAME_LABEL = 'Game';

    public function toArray()
    {
        $array = parent::toArray();
        $array['additionalLabels'][] = self::GAME_LABEL;

        return $array;
    }
}