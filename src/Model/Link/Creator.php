<?php

namespace Model\Link;

class Creator extends Link
{
    const CREATOR_LABEL = 'Creator';
    //TODO: Related User/GhostUser ?

    public function toArray()
    {
        $array = parent::toArray();
        if (!in_array(self::CREATOR_LABEL, $array['additionalLabels'])) {
            $array['additionalLabels'][] = self::CREATOR_LABEL;
        }

        return $array;
    }
}