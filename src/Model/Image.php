<?php

namespace Model;

class Image extends Link
{
    public function isComplete() {
        return !!$this->getUrl();
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['additionalLabels'] = array('Image');
        return $array;
    }
}