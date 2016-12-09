<?php

namespace Model;

class Image extends Link
{
    public function isComplete() {
        return !!$this->getUrl();
    }
}