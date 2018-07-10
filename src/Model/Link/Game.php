<?php

namespace Model\Link;

class Game extends Link
{
    const GAME_LABEL = 'Game';

    public function toArray()
    {
        $array = parent::toArray();
        if (!in_array(self::GAME_LABEL, $array['additionalLabels'])) {
            $array['additionalLabels'][] = self::GAME_LABEL;
        }

        return $array;
    }
}